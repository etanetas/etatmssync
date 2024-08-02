from peewee import *
from lib import lms_db
import sys
from playhouse.shortcuts import model_to_dict

class BaseModel(Model):
    class Meta:
        database = lms_db


class Customer(BaseModel):
    id = IntegerField(db_column='id')
    name = CharField(db_column='name')
    lastname = CharField(db_column='lastname')
    status = IntegerField(db_column='status')
    pin = CharField(db_column='pin')

    class Meta:
        table_name = 'customers'


class Node(BaseModel):
    id = IntegerField(db_column='id')
    name = CharField(db_column='name')
    ownerid = ForeignKeyField(Customer, db_column='ownerid', backref='nodes')
    info = TextField(db_column='info')
    access = IntegerField(db_column='access')
    authtype = IntegerField(db_column='authtype')

    class Meta:
        table_name = 'nodes'


class Macs(BaseModel):
    id = IntegerField(db_column='id')
    macaddr = CharField(db_column='mac')
    nodeid = ForeignKeyField(Node, db_column='nodeid', backref='macs')

    class Meta:
        table_name = 'macs'


class Assignment(BaseModel):
    id = IntegerField(db_column='id')
    tariffid = IntegerField(db_column='tariffid')
    customerid = ForeignKeyField(Customer, db_column='customerid', backref='assignments')
    commited = IntegerField(db_column='commited')
    suspended = IntegerField(db_column='suspended')
    datefrom = IntegerField(db_column='datefrom')
    dateto = IntegerField(db_column='dateto')

    class Meta:
        table_name = 'assignments'


class Node_Assignments(BaseModel):
    id = IntegerField(db_column='id')
    assignmentid = IntegerField(db_column='assignmentid')
    nodeid = ForeignKeyField(Node, db_column='nodeid', backref='assignments')

    class Meta:
        table_name = 'nodeassignments'


class Tms_Settings(BaseModel):
    host = CharField()
    user = CharField()
    passwd = CharField()
    provider = IntegerField()
    login_pattern = CharField()
    sync_stb = BooleanField()
    additional_devices = IntegerField()

class Tms_Plans(BaseModel):
    tmstarif = CharField()
    lmstarif = CharField()

try:
    lms_db.connect(reuse_if_open=True)

except Exception as e:
    lms_db.close()
    sys.exit('Can\'t connect to database ' + str(e))
