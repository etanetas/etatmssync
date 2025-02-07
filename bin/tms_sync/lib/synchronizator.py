import logging
from lib.api import Api
from lib.log import getLogger
import hashlib
import re

logger = getLogger()

class Synchronizator(object):
    cust_plans = {}
    node_plans = {}
    node_macs = {}
    done = []
    further_cust = []
    login_pattern = "lms_%cid"
    sync_stb = True
    additional_devices = -1

    def __init__(self, host="", username="", password="", provider=0, login_pattern="lms_%cid", sync_stb=True, additional_devices=-1):
      if login_pattern != "":
        self.login_pattern = login_pattern
      if additional_devices != -1:
        self.additional_devices = additional_devices
      self.sync_stb  = sync_stb
      self.api = Api(host, username, password, provider)
      return
    
    def set_cust_plans(self, cust_plans):
        for cust in cust_plans:
            self.cust_plans[cust] = set(cust_plans[cust])
    
    def set_node_plans(self, node_plans):
        for node in node_plans:
            self.node_plans[node] = set(node_plans[node])
    
    def set_node_macs(self, node_macs):
        self.node_macs = dict(node_macs)

    def customer_act(self, cust, login):
        name = '{} {}'.format(cust['lastname'], cust['name'])
        name = name.replace("\"","")
        name = name.replace("'","")
        logger.info('Handling %s %s devices %d', cust['name'], login, cust['devices'])
        cust_info = self.api.get_accounts(login)
        devices_count = -1
        if self.additional_devices == -1:
            devices_count = -1
        else:
            if self.sync_stb:
                devices_count = cust['devices']
            if self.additional_devices > 0:
                devices_count += self.additional_devices


        if cust_info['total'] > 0:
            pin = hashlib.md5(cust['pin'].encode())
            pin = pin.hexdigest()
            modified = False
            if devices_count == -1:
                compare_devices_count = None
            else:
                compare_devices_count = devices_count
            if name != cust_info['data'][0]['fullname']:
                modified = True
                logger.debug("Name changed from %s to %s", cust_info['data'][0]['fullname'], name)
            if pin != cust_info['data'][0]['pin_md5']:
                modified = True
                logger.debug("Pin changed from %s to %s", cust_info['data'][0]['pin_md5'], pin)
            if compare_devices_count != cust_info['data'][0]['devices_per_account_limit']:
                modified = True
                logger.debug("Devices count changed from %s to %s", cust_info['data'][0]['devices_per_account_limit'], devices_count)
            if modified:
                self.api.modify_account(cust_info['data'][0]['id'], 'true', name, login, pin, devices_count)
                logger.info('%s %s modified', name, login)
            else:
                logger.info("%s %s not changed",name, login)
        else:
            pin = hashlib.md5(cust['pin'].encode())
            pin = pin.hexdigest()
            self.api.create_account(name, login, pin, devices_count)
            logger.info('%s %s created', name, login)
            cust_info = self.api.get_accounts(login)
        acc_sub = self.api.get_acc_subscription(cust_info['data'][0]['id'])['data']
        for sub, id in [(sub['tarif'], sub['id']) for sub in acc_sub]:
            if cust['customerid'] in self.cust_plans and sub in self.cust_plans[cust['customerid']]:
                self.cust_plans[cust['customerid']].remove(sub)
                continue
            self.api.delete_acc_subscription(id)
        for plan in self.cust_plans[cust['customerid']]:
            if int(plan) not in [sub['tarif'] for sub in acc_sub]:
                self.api.set_acc_subscription(cust_info['data'][0]['id'], plan)

    def is_mac_address(self, unique_id):
        """
        This function checks if a string is formatted like a MAC address.
        Args:
            string: The string to check.

        Returns:
            True if the string is formatted like a MAC address, False otherwise.
        """
        pattern = "^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$"
        return bool(re.match(pattern, unique_id))


    def devices_act(self, cust, cust_info, nodes):
        cust_devs = self.api.get_devices(cust_info['data'][0]['id'])['data']
        cust_dev_list = []
        for each in cust_devs:
            cust_dev_list.append(each['unique_id'])
        cust_nodes = nodes[int(cust['customerid'])]
        logger.info('Handling devices %s for customer %s', cust_nodes, cust['customerid'])
        for node in cust_nodes:
            if node in cust_dev_list:
                continue
            device = self.api.get_device_by_mac(node)
            if device['total'] > 0:
                self.api.delete_device(device['data'][0]['id'])
            self.api.create_device(cust_info['data'][0]['id'], node)
        for cust_dev in cust_dev_list:
            if not self.is_mac_address(cust_dev):
                logger.debug("Skipping not decoder device %s", cust_dev)
                continue
            if cust_dev in cust_nodes:
                continue
            device = self.api.get_device_by_mac(cust_dev)
            self.api.delete_device(device['data'][0]['id'])

    def set_dev_subs(self):
        account = set()
        for nodeid, mac in self.node_macs.items():
            device = self.api.get_device_by_mac(mac)
            if nodeid not in self.node_plans:
                account.add(device['data'][0]['account'])
                continue
            plan = self.node_plans[nodeid]
            self.done.append(mac)
            account.add(device['data'][0]['account'])
            dev_sub = self.api.get_dev_subscription(device['data'][0]['id'])['data']
            substrat = [(sub['tarif'], sub['id']) for sub in dev_sub]
            subs = set(sub['tarif'] for sub in dev_sub)
            for one in plan:
                if one in subs:
                    continue
                logger.info('Device %s subscription set to %s', mac, one)
                self.api.set_dev_subscription(device['data'][0]['id'], one)
            for tar, id in substrat:
                if tar in plan:
                    plan.remove(tar)
                    continue
                logger.info('Device %s subscription %s removed', mac, id)
                self.api.delete_dev_subscription(id)
        for acc in account:
            userdevs = [(id['id'],id['unique_id']) for id in self.api.get_devices(acc)['data']]
            for one_dev, one_mac in userdevs:
                if one_mac in self.done:
                    continue
                subs = [sub['id'] for sub in self.api.get_dev_subscription(one_dev)['data']]
                for sub in subs:
                    logger.info('Device %s subscription %s removed', one_mac, subs)
                    self.api.delete_dev_subscription(sub)

    def purge_this(self):
        logger.info('Looking for something to remove')
        # login_const = self.login_pattern.replace('%cid', '')
        login_pattern = re.compile(self.login_pattern.replace("%cid", r"\d+"))

        for tms_customer in self.api.get_accounts()['data']:
            if bool(login_pattern.fullmatch(tms_customer['login'])):
                if tms_customer['login'] not in self.further_cust:
                    devices = self.api.get_devices(tms_customer['id'])
                    for each in devices['data']:
                        self.api.delete_device(each['id'])
                    self.api.delete_account(tms_customer['id'])
                    logger.info('Removed customer %s', tms_customer['login'])
                    continue
                self.further_cust.remove(tms_customer['login'])

    def delete_one(self, id):
        logger.info('Removing customer %s', id)
        login = self.login_pattern.replace('%cid', str(id))
        try:
            tms_customer = self.api.get_accounts(login)
            if self.sync_stb:
                devices = self.api.get_devices(tms_customer['data'][0]['id'])
                for each in devices['data']:
                    self.api.delete_device(each['id'])
            else:
                print("skipping devices sync, disabled in config")
            self.api.delete_account(tms_customer['data'][0]['id'])
            logger.info('Removed customer %s', tms_customer['data'][0]['login'])
        except IndexError:
            logger.info('Customer not found in TMS')

    def sync_all(self, custonze, nodes, updatable=False):
        for cust in custonze:
            login = self.login_pattern.replace('%cid', str(cust['customerid']))
            self.further_cust.append(login)
            self.customer_act(cust, login)
            cust_info = self.api.get_accounts(login)
            if self.sync_stb:
                self.devices_act(cust, cust_info, nodes)
        if self.sync_stb:
            self.set_dev_subs()
        if updatable:
            self.purge_this()

    def sync_single(self, cust, nodes):
        login = self.login_pattern.replace('%cid', str(cust['customerid']))
        self.further_cust.append(login)
        self.customer_act(cust, login)
        cust_info = self.api.get_accounts(login)
        if self.sync_stb:
            self.devices_act(cust, cust_info, nodes)
        if self.sync_stb:
            self.set_dev_subs()

    def update(self, customer, nodes):
        self.cust_plans.clear()
        self.node_plans.clear()
        self.node_macs.clear()
        self.done.clear()
        self.further_cust.clear()
        self.sync_all(customer, nodes)
