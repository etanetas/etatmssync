# Wymagania:
* python 3
* python3-pip
* python3-psycopg2

# Instalacja:
1. Zawartość wtychki umieścić w katalogu plugins/EtaTmsSync w LMS.
2. Utworzyć dowiązanie symboliczne w katalogu img LMS-a o nazwie etatmssync do katalogu ../plugins/EtaTmsSync/img.
3. Utworzyć dowiązanie symboliczne w katalogu js LMS-a o nazwie etatmssync do katalogu ../plugins/EtaTmsSync/js.
4. Utworzyć dowiązanie symboliczne w katalogu css LMS-a o nazwie etatmssync do katalogu ../plugins/EtaTmsSync/css.
5. Zainstalować wymagane pakiety z bin/tms_sync/requirements.txt (pip3 install -r bin/tms_sync/requirements.txt)
6. Konfiguracja SELinux: Zezwolenie na uruchamianie skryptów przez serwer www
```
  semanage fcontext -a -t httpd_sys_script_exec_t "/var/www/html/lmsplus/plugins/EtaTmsSync/bin(/.*)?"
  restorecon -R /var/www/html/lmsplus/plugins/EtaTmsSync/bin
```

# Konfiguracja
W sekcji menu TMS Sync -> Configurations ustawiamy dane do serwera TMS.
W sekcji menu TMS Sync -> Tarifs ustawiamy linki taryf TMS z taryfami LMS.

# Skrypt
Skrypt bin/tms_sync/sync.py pozwala na synchronizowanie danych LMS i TMS.
Przy uruchomieniu skryptu bez opcji wykonuje pełną synchronizację.
Uruchomiony z opcją -s {customerid} (np. -s 1) pozwala na synchronizowanie danych dla konkretnego klienta.
