Installation

Install plugin to lms (cd <lmsdir>/plugins && git clone https://github.com/etanetas/etatmssync.git EtaTmsSync)
Install python3 requirements for tms_sync script (cd <lmsdir>/plugins/EtaTmsSync/bin/tms_sync && pip3 install -r requirements.txt)
Create log file (touch /var/log/tms_sync.log)
Add permission for web server to write to this file (chown www-data.www-data /var/log/tms_sync.log && chmod 0644 /var/log/tms_sync.log)

--- lms version 24+ ---
create link to plugin css folder (ln -s <lmsdir>/plugins/EtaTmsSync/css/ <lmsdir>/css/etatmssync)
create link to plugin js folder (ln -s <lmsdir>/plugins/EtaTmsSync/js/ <lmsdir>/js/etatmssync)