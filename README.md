Installation
create log file (touch /var/log/tms_sync.log)
add permission for web server to write to this file (chown www-data.www-data /var/log/tms_sync.log && chmod 0644 /var/log/tms_sync.log)

--- lms version 24+ ---
create link to plugin css folder (ln -s <lmsdir>/plugins/EtaTmsSync/css/ <lmsdir>/css/etatmssync)
create link to plugin js folder (ln -s <lmsdir>/plugins/EtaTmsSync/js/ <lmsdir>/js/etatmssync)

--- lms older version --- 
use branch old
