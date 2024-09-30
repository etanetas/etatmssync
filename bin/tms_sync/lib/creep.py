from lib.models import *
from lib.synchronizator import Synchronizator
from lib.log import getLogger
import time
import os
import pickle
import traceback
import logging


tms_plan = Tms_Plans.select()
plans = {x.tmstarif: list(map(int, x.lmstarif.split(','))) for x in tms_plan}

logging.getLogger('requests').setLevel(logging.WARNING)
logging.getLogger('urllib3').setLevel(logging.WARNING)
logger = getLogger()

class Main(object):

    synchronizers = []
    cust_plans = {}
    node_plans = {}
    node_macs = {}

    def __init__(self):
        pass
        # if not os.path.exists(log_file): 
        #     with open(log_file, 'w') as file: 
        #         pass

    def get_all_customers(self, id=None):
        """Get customers and node ids for specified tariffs. If ID is set, then select only one customer.
        Else, select all customers. """
        customers = []
        for plan_type, tariffs in plans.items():
            logger.debug('TMS plan %s for LMS plans %s', plan_type, tariffs)
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
            cust['devices'] = len(tvips)
            count +=len(tvips)
        logger.info('Nodes fetched: %s', count)
        return customer_nodes

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
        tms_list = Tms_Settings.select()
        for tms in tms_list:
            synchronizer = Synchronizator(tms.host, tms.user, tms.passwd, tms.provider, tms.login_pattern, tms.sync_stb, tms.additional_devices)
            self.synchronizers.append(synchronizer)
        try:
            start = time.time()
            if args.sync:
                customers = self.get_all_customers(args.sync)
                if len(customers) == 0:
                    for s in self.synchronizers:
                        s.delete_one(args.sync)
                elif len(customers) == 1:
                    nodes = self.get_all_nodes(customers)
                    for s in self.synchronizers:
                        s.set_cust_plans(self.cust_plans)
                        s.set_node_plans(self.node_plans)
                        s.set_node_macs(self.node_macs)
                        s.sync_single(customers[0], nodes)
                else:
                    logger.error("To many customers found with id %s", args.sync)
                    return
            elif args.update:
                updatable = self.get_difference()
                customers = []
                for one in updatable:
                    cust = self.get_all_customers(one)
                    nodes = self.get_all_nodes(cust)
                    for s in self.synchronizers:
                        s.set_cust_plans(self.cust_plans)
                        s.set_node_plans(self.node_plans)
                        s.set_node_macs(self.node_macs)
                        s.sync_all(cust, nodes)
            elif args.delete:
                for s in self.synchronizers:
                    s.delete_one(args.delete)
            else:
                customers = self.get_all_customers()
                nodes = self.get_all_nodes(customers)
                for s in self.synchronizers:
                    s.set_cust_plans(self.cust_plans)
                    s.set_node_plans(self.node_plans)
                    s.set_node_macs(self.node_macs)
                    s.sync_all(customers, nodes, True)
            end = time.time()
            final = round(end-start, 1)
            logger.info('Process finished in %s sec', final)
        except Exception as err:
            lms_db.close()
            logger.error(traceback.format_exc())
            logger.error(err)
        finally:
            lms_db.close()
