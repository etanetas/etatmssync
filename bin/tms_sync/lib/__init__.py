import peewee
from config import Config
import sys


cfg = Config()

if cfg.db_type == 'postgres':
    lms_db = peewee.PostgresqlDatabase(cfg.db_name, user=cfg.db_user, password=cfg.db_passwd,
                                       host=cfg.db_host, port=5432)
elif cfg.db_type == 'mysql':
    lms_db = peewee.MySQLDatabase(cfg.db_name, user=cfg.db_user, password=cfg.db_passwd,
                                  host=cfg.db_host, port=3316)
else:
    sys.exit('Database type not recognized')

from lib import api, models

# try:
#     lms_db.connect()
#     lms_db.create_tables([models.Tms_Settings, models.Tms_Plans])
#
# except Exception as e:
#     sys.exit('Can\'t connect to database ' + str(e))