import logging
from lib.api import Api
import hashlib

logger = logging.getLogger(__name__)


class Synchronizator(object):
    cust_plans = {}
    node_plans = {}
    node_macs = {}
    obrabotano = []
    further_cust = []

    def __init__(self, host="", username="", password="", provider=0):
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
        cust_info = self.api.get_accounts(login)
        if cust_info['total'] > 0:
            pin = hashlib.md5(cust['pin'].encode())
            pin = pin.hexdigest()
            modified = False
            if name != cust_info['data'][0]['fullname']:
                modified = True
            if pin != cust_info['data'][0]['pin_md5']:
                modified = True
            if modified:
                self.api.modify_account(cust_info['data'][0]['id'], 'true', name, login, pin)
                logger.info('%s %s modified', name, login)
            else:
                logger.info("%s %s not changed",name, login)
        else:
            pin = hashlib.md5(cust['pin'].encode())
            pin = pin.hexdigest()

            self.api.create_account(name, login, pin)
            logger.info('%s %s created', name, login)
            cust_info = self.api.get_accounts(login)
        acc_sub = self.api.get_acc_subscription(cust_info['data'][0]['id'])['data']
        for sub, id in [(sub['tarif'], sub['id']) for sub in acc_sub]:
            if sub in self.cust_plans[cust['customerid']]:
                self.cust_plans[cust['customerid']].remove(sub)
                continue
            self.api.delete_acc_subscription(id)
        for plan in self.cust_plans[cust['customerid']]:
            if int(plan) not in [sub['tarif'] for sub in acc_sub]:
                self.api.set_acc_subscription(cust_info['data'][0]['id'], plan)

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
            if cust_dev in cust_nodes:
                continue
            device = self.api.get_device_by_mac(cust_dev)
            self.api.delete_device(device['data'][0]['id'])

    def set_dev_subs(self):
        account = set()
        logger.info('Now\'s time for some device subscription magic...')
        for nodeid, mac in self.node_macs.items():
            device = self.api.get_device_by_mac(mac)
            if nodeid not in self.node_plans:
                account.add(device['data'][0]['account'])
                continue
            plan = self.node_plans[nodeid]
            self.obrabotano.append(mac)
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
                if one_mac in self.obrabotano:
                    continue
                subs = [sub['id'] for sub in self.api.get_dev_subscription(one_dev)['data']]
                for sub in subs:
                    logger.info('Device %s subscription %s removed', one_mac, subs)
                    self.api.delete_dev_subscription(sub)

    def purge_this(self):
        logger.info('Looking for something to remove')
        for tms_customer in self.api.get_accounts()['data']:
            if 'lms' in tms_customer['login']:
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
        login = 'lms_{}'.format(str(id))
        try:
            tms_customer = self.api.get_accounts(login)
            devices = self.api.get_devices(tms_customer['data'][0]['id'])
            for each in devices['data']:
                self.api.delete_device(each['id'])
            self.api.delete_account(tms_customer['data'][0]['id'])
            logger.info('Removed customer %s', tms_customer['data'][0]['login'])
        except IndexError:
            logger.info('Customer not found in TMS')

    def sync_all(self, custonze, nodes):
        for cust in custonze:
            login = 'lms_{}'.format(str(cust['customerid']))
            self.further_cust.append(login)
            self.customer_act(cust, login)
            cust_info = self.api.get_accounts(login)
            self.devices_act(cust, cust_info, nodes)
        self.set_dev_subs()
        if len(custonze) > 1:
            self.purge_this()

    def update(self, updatable):
        for one in updatable:
            self.cust_plans.clear()
            self.node_plans.clear()
            self.node_macs.clear()
            self.obrabotano.clear()
            self.further_cust.clear()
            customers = self.get_all_customers(one)
            self.sync_all(customers)