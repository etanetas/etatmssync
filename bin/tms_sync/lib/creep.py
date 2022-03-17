from lib.models import *
from lib.api import Api
import time
import logging
import pickle

tms_plan = Tms_Plans.select()
plans = {x.tmstarif: list(map(int, x.lmstarif.split(','))) for x in tms_plan}
logging.basicConfig(filename='/var/log/tms_sync.log', format='%(asctime)s - %(levelname)s - %(message)s',
                    datefmt='%d-%b-%y %H:%M:%S', level=logging.INFO)
logging.getLogger('requests').setLevel(logging.WARNING)
logging.getLogger('urllib3').setLevel(logging.WARNING)
# logging.getLogger('peewee').setLevel(logging.DEBUG)
logger = logging.getLogger(__name__)


class Synchronizator(object):
    api = Api()
    cust_plans = {}
    node_plans = {}
    node_macs = {}
    obrabotano = []
    further_cust = []

    def get_all_customers(self, id=None):
        """Get customers and node ids for specified tariffs. If ID is set, then select only one customer.
        Else, select all customers. """
        customers = []
        for plan_type, tariffs in plans.items():
            logger.info('TMS plan %s for LMS plans %s', plan_type, tariffs)
            for tariff in tariffs:
                customers_tar = []
                if id:
                    query = (Assignment.select(Assignment.customerid, Node_Assignments.nodeid,
                                               Customer.name, Customer.lastname, Customer.pin)
                             .join_from(Assignment, Customer, on=(Assignment.customerid == Customer.id), attr='customer')
                             .join_from(Assignment, Node_Assignments, join_type='LEFT JOIN',
                                        on=(Assignment.id == Node_Assignments.assignmentid), attr='assignmentid')
                             .where(Assignment.tariffid == tariff,
                                    Assignment.customerid == id,
                                    Assignment.datefrom < int(time.time()),
                                    (Assignment.dateto > int(time.time())) | (Assignment.dateto == 0),
                                    Assignment.suspended == 0,
                                    Customer.status == 3)
                             .dicts())
                else:
                    query = (Assignment.select(Assignment.customerid, Node_Assignments.nodeid,
                                               Customer.name, Customer.lastname, Customer.pin)
                             .join_from(Assignment, Customer, on=(Assignment.customerid == Customer.id), attr='customer')
                             .join_from(Assignment, Node_Assignments, join_type='LEFT JOIN',
                                        on=(Assignment.id == Node_Assignments.assignmentid), attr='assignmentid')
                             .where(Assignment.tariffid == tariff,
                                    Assignment.datefrom < int(time.time()),
                                    (Assignment.dateto > int(time.time())) | (Assignment.dateto == 0),
                                    Assignment.suspended == 0,
                                    Customer.status == 3)
                             .dicts())
                for each in query:
                    if each['nodeid']:
                        self.node_plans.setdefault(each['nodeid'], set()).add(int(plan_type))
                    self.cust_plans.setdefault(each['customerid'], set()).add(int(plan_type))
                    customers_tar.append(each)
                    # customers_tar = list({v['customerid']: v for v in customers_tar}.values())
                customers += customers_tar
        customers = list({v['customerid']: v for v in customers}.values())
        logger.info('Customers fetched: %s', len(customers))
        return customers

    def get_all_nodes(self, custonze):
        """Get all nodes in dict with customerid as a key.
        Then if it's TVIP mac add mac to macs list.
        If it's SELTEKA then parse node info, find and prepare sn to look like mac."""
        customer_nodes = {}
        count = 0
        for cust in custonze:
            node_query = (Node.select(Node.info, Node.id, Node.authtype, Macs.macaddr)
                          .join_from(Node, Macs, on=(Node.id == Macs.nodeid), attr='mac')
                          .where(Node.ownerid == cust['customerid'],
                                 Node.access == 1).dicts())
            tvips = []
            for node in node_query:
                mac_addr = node['macaddr'].lower()
                if '10:27:be' in mac_addr:  # parsing TVIP STB mac
                    tvips.append(mac_addr)
                    self.node_macs[node['id']] = mac_addr
                elif 'e4:ab:46' in mac_addr:  # parsing SELTEKA 2-3 SN as a mac
                    try:
                        info = node['info'].lower()
                        info = info.split('b495',1)
                        info = info[0][-7:]+info[1][:5]
                        info = ':'.join(format(s, '02x') for s in bytes.fromhex(info))
                        tvips.append(info)
                        self.node_macs[node['id']] = info
                    except:
                        pass
                elif node['authtype'] == 32:
                    tvips.append(mac_addr)
                    self.node_macs[node['id']] = mac_addr
            customer_nodes[cust['customerid']] = tvips
            count +=len(tvips)
        logger.info('Nodes fetched: %s', count)
        return customer_nodes

    def customer_act(self, cust, login):
        name = '{} {}'.format(cust['lastname'], cust['name'])
        cust_info = self.api.get_accounts(login)
        if cust_info['total'] > 0:
            self.api.modify_account(cust_info['data'][0]['id'], 'true', name, login, cust['pin'])
            logger.info('%s %s modified', name, login)
        else:
            self.api.create_account(name, login, cust['pin'])
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
                    # print(subs, userdevs)
                    logger.info('Device %s subscription %s removed', one_mac, subs)
                    self.api.delete_dev_subscription(sub)

    def purge_this(self):
        logger.info('Looking for something to remove')
        for tms_customer in self.api.get_accounts()['data']:
            if 'lms' in tms_customer['login']:
                devices = self.api.get_devices(tms_customer['id'])
                if tms_customer['login'] not in self.further_cust:
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

    def sync_all(self, custonze):
        nodes = self.get_all_nodes(custonze)
        for cust in custonze:
            login = 'lms_{}'.format(str(cust['customerid']))
            self.further_cust.append(login)
            self.customer_act(cust, login)
            cust_info = self.api.get_accounts(login)
            self.devices_act(cust, cust_info, nodes)
        self.set_dev_subs()
        if len(custonze) > 1:
            self.purge_this()

    def get_difference(self):
        tariffs = [tariff for tms_plans, lms_plans in plans.items() for tariff in lms_plans]
        renew = set()

        with open('customers_prev.pickle', 'ab+') as f:
            f.seek(0)
            try:
                customers_prev = pickle.load(f)
            except EOFError:
                customers_prev = {}
            set_customers_prev = set(tuple(sorted(d.items())) for d in customers_prev)


        cust = (Customer.select(Customer.id, Customer.name, Customer.lastname, Customer.status, Customer.pin,
                                Node.id.alias('nodeid'), Node.name.alias('nodename'), Node.ownerid,
                                Node.info.alias('nodeinfo'), Node.access.alias('nodeaccess'), Node.authtype.alias('nodeauthtype'),
                                Node_Assignments.assignmentid.alias('nodeassid'),
                                Macs.macaddr, Assignment.tariffid, Assignment.commited, Assignment.suspended, Assignment.datefrom,
                                Assignment.dateto)
                .join(Node)
                .join(Node_Assignments, JOIN.LEFT_OUTER)
                .switch(Node)
                .join(Macs, JOIN.LEFT_OUTER)
                .switch(Customer)
                .join(Assignment)
                .where(Assignment.tariffid.in_(tariffs),
                       Assignment.datefrom < int(time.time()),
                       (Assignment.dateto > int(time.time())) | (Assignment.dateto == 0),
                       Assignment.suspended == 0,
                       Customer.status == 3,
                       Node.access == 1)
                ).dicts()

        all_customers = [x for x in cust]
        with open('customers_prev.pickle', 'wb') as f:
            pickle.dump(all_customers, f)
        set_all_customers = set(tuple(sorted(d.items())) for d in all_customers)

        diff = set_customers_prev ^ set_all_customers
        for one in diff:
            differed = dict((x, y) for x, y in one)
            renew.add(differed['id'])

        return renew


    def main(self, args):
        try:
            start = time.time()
            if args.sync:
                customers = self.get_all_customers(args.sync)
                self.sync_all(customers)
            elif args.update:
                updatable = self.get_difference()
                # print(updatable)
                for one in updatable:
                    self.cust_plans.clear()
                    self.node_plans.clear()
                    self.node_macs.clear()
                    self.obrabotano.clear()
                    self.further_cust.clear()
                    customers = self.get_all_customers(one)
                    self.sync_all(customers)
            elif args.delete:
                self.delete_one(args.delete)
            else:
                customers = self.get_all_customers()
                self.sync_all(customers)
            end = time.time()
            final = round(end-start, 1)
            logger.info('Process finished in %s sec', final)
        except Exception as err:
            lms_db.close()
            logger.error(err)
