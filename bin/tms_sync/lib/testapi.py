from lib.models import Tms_Settings
import requests
import hashlib
import time


class TestApi(object):
    print("test api mode")
    cfg = Tms_Settings.get()
    url = '{}/api/provider/'.format(cfg.host)
    auth = (cfg.user, cfg.passwd)
    provider = cfg.provider
    current_time = time.strftime('%Y-%m-%dT%H:%M:%S%z')

    def get_accounts(self, client_id=None, url=url):
        if not client_id:
            url += 'accounts/?limit=0'
        else:
            url += 'accounts/?login={}'.format(client_id)
        response = requests.get(url, auth=self.auth)
        return response.json()

    def create_account(self, fullname, login, pin, provider=provider, url=url):
        print("create_account")
        # url += 'accounts'
        # headers = {'Content-Type': 'application/json'}
        # pin = hashlib.md5(pin.encode())
        # if '"' in fullname:
        #     fullname = fullname.replace('"', '')
        # data = """{{
        #           "devices_per_account_limit": 10,
        #           "enabled": true,
        #           "fullname": "{}",
        #           "login": "{}",
        #           "pin_md5": "{}",
        #           "provider": {}
        # }}""".format(fullname, login, pin.hexdigest(), provider)  # hashlib.md5('9113'.encode())  pin.hexdigest()
        # response = requests.post(url, data.encode('utf-8'), auth=self.auth, headers=headers)
        # return response.json()

    def modify_account(self, client_id, state, fullname, login, pin, provider=provider, url=url):
        print("modify_account")
        print(client_id)
        print(state)
        print(fullname)
        print(login)
        print(pin)
        print(provider)
        print(url)
        return
        # url += 'accounts'
        # headers = {'Content-Type': 'application/json'}
        # pin = hashlib.md5(pin.encode())
        # data = """{{
        #           "id": {},
        #           "devices_per_account_limit": 10,
        #           "enabled": {},
        #           "fullname": "{}",
        #           "login": "{}",
        #           "pin_md5": "{}",
        #           "provider": {}
        # }}""".format(client_id, state, fullname, login, pin.hexdigest(),
        #              provider)  # hashlib.md5('9113'.encode())  pin.hexdigest()
        # response = requests.post(url, data.encode('utf-8'), auth=self.auth, headers=headers)
        # return response.json()

    def delete_account(self, client_id, url=url):
        print("delete account")
        return
        # url += 'accounts/{}'.format(client_id)
        # response = requests.delete(url, auth=self.auth)
        # return response.json()

    def get_acc_subscription(self, client_id=None, url=url):
        if client_id:
            url += 'account_subscriptions/?account={}'.format(client_id)
        else:
            url += 'account_subscriptions/'
        response = requests.get(url, auth=self.auth)
        return response.json()

    def set_acc_subscription(self, client_id, tarif_id, time=current_time, url=url):
        print("set_acc_subscription")
        return
        # url += 'account_subscriptions/'
        # headers = {'Content-Type': 'application/json'}
        # data = """{{
        #           "account": {},
        #           "tarif": {},
        #           "start": "{}"
        #         }}""".format(client_id, tarif_id, time)
        # response = requests.post(url, data, auth=self.auth, headers=headers)
        # return response.json()

    def delete_acc_subscription(self, subs_id, url=url):
        print("delete_acc_subscription id: {}".format(subs_id))
        return
        # url += 'account_subscriptions/{}'.format(subs_id)
        # response = requests.delete(url, auth=self.auth)
        # return response.json()

    def get_devices(self, client_id=None, url=url):
        if client_id:
            url += 'devices?account={}'.format(client_id)
        else:
            url += 'devices/?limit=0'
        response = requests.get(url, auth=self.auth)
        return response.json()

    def get_device_by_mac(self, unique_id, url=url):
        url += 'devices/?unique_id={}'.format(unique_id)
        response = requests.get(url, auth=self.auth)
        return response.json()

    def create_device(self, client_id, mac, provider=provider, url=url):
        print("create_device")
        return
        # url += 'devices'
        # headers = {'Content-Type': 'application/json'}
        # data = """{{
        #           "account": {},
        #           "provider": {},
        #           "unique_id": "{}"
        # }}""".format(client_id, provider, mac)
        # response = requests.post(url, data, auth=self.auth, headers=headers)
        # return response.json()

    def delete_device(self, dev_id, url=url):
        print("delete_device")
        return
        # url += 'devices/{}'.format(dev_id)
        # response = requests.delete(url, auth=self.auth)
        # return response.json()

    def get_dev_subscription(self, device_id, url=url):
        url += 'device_subscriptions/?device={}'.format(device_id)
        response = requests.get(url, auth=self.auth)
        return response.json()

    def set_dev_subscription(self, device_id, tariff_id, time=current_time, url=url):
        print("set_dev_subscription")
        return
        # url += 'device_subscriptions/'
        # headers = {'Content-Type': 'application/json'}
        # data = """{{
        #           "device": {},
        #           "tarif": {},
        #           "start": "{}"
        #         }}""".format(device_id, tariff_id, time)
        # response = requests.post(url, data, auth=self.auth, headers=headers)
        # return response.json()

    def delete_dev_subscription(self, subs_id, url=url):
        print("delete_dev_subscription")
        return
        # url += 'device_subscriptions/{}'.format(subs_id)
        # response = requests.delete(url, auth=self.auth)
        # return response.json()
