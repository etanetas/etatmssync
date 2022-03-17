import configparser
import sys
import argparse


class Config(object):

    parser = argparse.ArgumentParser(description='LMS and TMS Sync')
    parser.add_argument(
        '-s',
        '--sync',
        type=int,
        default=None,
        help='Type id of LMS customer (one per call)'
    )
    parser.add_argument(
        '-u',
        '--update',
        action='store_true',
        help='Update new changes (only additions are made, no deletions)'
    )
    parser.add_argument(
        "-c",
        "--config",
        default='/etc/lms/lms.ini',
        help='Set lms.ini path. For example: --config /etc/lms/lms.ini (this path is already default)'
    )
    parser.add_argument(
        '-d',
        '--delete',
        type=int,
        default=None,
        help='Delete customer by id'
    )
    args = parser.parse_args()

    config = configparser.ConfigParser(allow_no_value=True)

    try:
        config.read_file(open(args.config))
    except FileNotFoundError:
        sys.exit('LMS configuration file not found.')
    db_type = config['database']['type']
    db_host = config['database']['host'].replace("'", "")
    db_name = config['database']['database']
    db_user = config['database']['user']
    db_passwd = config['database']['password']
