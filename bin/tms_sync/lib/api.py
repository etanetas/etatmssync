from lib.models import Tms_Settings
import requests
import time
import logging
import os
logger = logging.getLogger(__name__)

class UnauthorizedException(Exception):
    def __init__(self, url="", data="", method="", message=""):
        self.message = "TMS Unauthorized access error"
        if message != "":
           self.message = message 
        if url:
            self.message += " URL: {}".format(url)
        if data: 
            self.message += " Data: {}".format(data)
        if method: 
            self.message += " Method: {}".format(method)
        super().__init__(self.message)

class PermissionException(Exception):
    def __init__(self, url="", data="", method="", message=""):
        self.message = "TMS permission error"
        if message != "":
           self.message = message 
        if url:
            self.message += " URL: {}".format(url)
        if data: 
            self.message += " Data: {}".format(data)
        if method: 
            self.message += " Method: {}".format(method)
        super().__init__(self.message)

class NotFoundException(Exception):
    def __init__(self, url="", data="", method="", message=""):
        self.message = "TMS not found error"
        if message != "":
           self.message = message 
        if url:
            self.message += " URL: {}".format(url)
        if data: 
            self.message += " Data: {}".format(data)
        if method: 
            self.message += " Method: {}".format(method)
        super().__init__(self.message)

class ApiException(Exception):
    def __init__(self, url="", data="", method="", message=""):
        self.message = "TMS Other error"
        if message != "":
           self.message = message 
        if url:
            self.message += " URL: {}".format(url)
        if data: 
            self.message += " Data: {}".format(data)
        if method: 
            self.message += " Method: {}".format(method)
        super().__init__(self.message)

class MockResponse:
    def __init__(self, json_data={}, status_code=200):
        self.status_code = status_code
        self.json_data = json_data

    def json(self):
        return self.json_data

class Api(object):
    cfg = Tms_Settings.get()
    username = ""
    password = ""
    host = ""
    provider = 0
    url = '{}/api/provider/'.format(host)
    current_time = time.strftime('%Y-%m-%dT%H:%M:%S%z')
    test_mode = False

    def log_format(self, message=""):
        new_message = "({}) ".format(self.host)
        return new_message + message

    def __init__(self, host="", username="", password="", provider=0):
        if host.strip() == "":
            raise Exception("TMS host is missing")
        if username.strip() == "":
            raise Exception("TMS username is missing")
        if password.strip() == "":
            raise Exception("TMS username is missing")
        if provider == 0:
            raise Exception("TMS provider missing")
        
        if os.environ.get('TESTMODE') == '1':
            self.test_mode = True
        
        self.host = host
        self.username = username
        self.password = password
        self.provider = provider
        self.auth = (username,password)
        self.url = '{}/api/provider/'.format(self.host)
    
    def get(self, url):
        logger.debug("sending request to url: %s, auth: %s", url, self.auth)
        response = requests.get(url, auth=self.auth)
        if response.status_code == 401:
            raise UnauthorizedException(url, method="GET")
        elif response.status_code == 403:
            raise PermissionException(url, method="GET")
        elif response.status_code == 404:
            raise NotFoundException(url, method="GET")
        elif response.status_code != 400 and response.status_code != 200:
            raise ApiException(url, method="GET")
        return response
    
    def post(self, url, data):
        if self.test_mode:
            print("[TEST MODE] Sending post request to url {}".format(url))
            print(data)
            return MockResponse({}, 201)
        headers = {'Content-Type': 'application/json'}
        response = requests.post(url, data.encode('utf-8'), auth=self.auth, headers=headers)
        if response.status_code == 401:
            raise UnauthorizedException(url, data.encode('utf-8'), method="POST")
        elif response.status_code == 403:
            raise PermissionException(url, data.encode('utf-8'), method="POST")
        elif response.status_code == 404:
            raise NotFoundException(url, data.encode('utf-8'), method="POST")
        elif response.status_code != 201 and response.status_code != 200:
            raise ApiException(url, data.encode('utf-8'), method="POST")
        return response
    
    def delete(self, url):
        if self.test_mode:
            print("[TEST MODE] Sending delete request to url {}".format(url))
            return MockResponse({}, 200)
        response = requests.delete(url, auth=self.auth)
        if response.status_code == 401:
            raise UnauthorizedException(url, method="DELETE")
        elif response.status_code == 403:
            raise PermissionException(url, method="DELETE")
        elif response.status_code == 404:
            raise NotFoundException(url, method="DELETE")
        elif response.status_code != 204 and response.status_code != 200:
            raise ApiException(url, method="DELETE")
        return response

    def get_accounts(self, client_id=None):
        if client_id:
            logger.info(self.log_format("Fetching account {}".format(client_id)))
        else:
            logger.info(self.log_format("Fetching accounts"))

        url = self.url
        if not client_id:
            url += 'accounts/?limit=0'
        else:
            url += 'accounts/?login={}'.format(client_id)
        response = self.get(url)
        return response.json()

    def create_account(self, fullname, login, pin):
        logger.info(self.log_format("Creating account {}".format(login)))
        url = self.url + 'accounts'
        if '"' in fullname:
            fullname = fullname.replace('"', '')
        data = """{{
                  "devices_per_account_limit": 10,
                  "enabled": true,
                  "fullname": "{}",
                  "login": "{}",
                  "pin_md5": "{}",
                  "provider": {}
        }}""".format(fullname, login, pin, self.provider)  # hashlib.md5('9113'.encode())  pin.hexdigest()
        response = self.post(url, data)
        logger.info(self.log_format("Account {} created".format(login)))
        return response.json()

    def modify_account(self, client_id, state, fullname, login, pin):
        logger.info(self.log_format("Modifing account {}".format(client_id)))
        url = self.url + 'accounts'
        data = """{{
                  "id": {},
                  "devices_per_account_limit": 10,
                  "enabled": {},
                  "fullname": "{}",
                  "login": "{}",
                  "pin_md5": "{}",
                  "provider": {}
        }}""".format(client_id, state, fullname, login, pin,
                     self.provider)  # hashlib.md5('9113'.encode())  pin.hexdigest()
        response = self.post(url, data)
        logger.info(self.log_format("Account {} modified".format(client_id)))
        return response.json()

    def delete_account(self, client_id):
        logger.info(self.log_format("Removing account {}".format(client_id)))
        url = self.url + 'accounts/{}'.format(client_id)
        response = self.delete(url)
        logger.info(self.log_format("Account {} removed".format(client_id)))
        return response.json()

    def get_acc_subscription(self, client_id=None):
        if client_id:
            logger.info(self.log_format("Fetching account {} subsriptions".format(client_id)))
        else:
            logger.info(self.log_format("Fetching account subsriptions"))
        url = self.url
        if client_id:
            url += 'account_subscriptions/?account={}'.format(client_id)
        else:
            url += 'account_subscriptions/'
        response = self.get(url)
        return response.json()

    def set_acc_subscription(self, client_id, tarif_id, time=current_time):
        logger.info(self.log_format("Creating account {} subscription (tariff {})".format(client_id, tarif_id)))
        url = self.url + 'account_subscriptions/'
        data = """{{
                  "account": {},
                  "tarif": {},
                  "start": "{}"
                }}""".format(client_id, tarif_id, time)
        response = self.post(url, data)
        logger.info(self.log_format("Account {} subscription (tariff: {}) created".format(client_id, tarif_id)))
        return response.json()

    def delete_acc_subscription(self, subs_id):
        logger.info(self.log_format("Removing account subscription {}".format(subs_id)))
        url = self.url + 'account_subscriptions/{}'.format(subs_id)
        response = self.delete(url)
        logger.info(self.log_format("Account subscription {} removed".format(subs_id)))
        return response.json()

    def get_devices(self, client_id=None):
        if client_id:
            logger.info(self.log_format("Fetching account {} devices".format(client_id)))
        else:
            logger.info(self.log_format("Fetching account devices"))
        url = self.url
        if client_id:
            url += 'devices?account={}'.format(client_id)
        else:
            url += 'devices/?limit=0'
        response = self.get(url)
        return response.json()

    def get_device_by_mac(self, unique_id):
        logger.info(self.log_format("Fetching devices by unique_id {}".format(unique_id)))
        url = self.url + 'devices/?unique_id={}'.format(unique_id)
        response = self.get(url)
        return response.json()

    def create_device(self, client_id, mac):
        logger.info(self.log_format("Creating account {} device with unique_id {}".format(client_id, mac)))
        url = self.url + 'devices'
        data = """{{
                  "account": {},
                  "provider": {},
                  "unique_id": "{}"
        }}""".format(client_id, self.provider, mac)
        response = self.post(url, data)
        logger.info(self.log_format("Account {} device with unique_id {} created".format(client_id, mac)))
        return response.json()

    def delete_device(self, dev_id):
        logger.info(self.log_format("Removing device id: {}".format(dev_id)))
        url = self.url + 'devices/{}'.format(dev_id)
        response = self.delete(url)
        logger.info(self.log_format("Device id: {} removed".format(dev_id)))
        return response.json()

    def get_dev_subscription(self, device_id):
        logger.info(self.log_format("Fetching device {} subscriptions".format(device_id)))
        url = self.url + 'device_subscriptions/?device={}'.format(device_id)
        response = self.get(url)
        return response.json()

    def set_dev_subscription(self, device_id, tariff_id, time=current_time):
        logger.info(self.log_format("Creating device {} subscription (tariff {})".format(device_id, tariff_id)))
        url = self.url + 'device_subscriptions/'
        data = """{{
                  "device": {},
                  "tarif": {},
                  "start": "{}"
                }}""".format(device_id, tariff_id, time)
        response = self.post(url, data)
        logger.info(self.log_format("Device {} subscription (tariff {}) created".format(device_id, tariff_id)))
        return response.json()

    def delete_dev_subscription(self, subs_id):
        logger.info(self.log_format("Removing device subscription {}".format(subs_id)))
        url = self.url +'device_subscriptions/{}'.format(subs_id)
        response = self.delete(url)
        logger.info(self.log_format("Device subscription {} removed".format(subs_id)))
        return response.json()
