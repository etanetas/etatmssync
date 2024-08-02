<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE tms_settings ADD COLUMN additional_devices INTEGER");
$this->Execute("ALTER TABLE tms_settings ALTER COLUMN additional_devices SET DEFAULT 0");
$this->Execute("UPDATE tms_settings SET additional_devices = 0");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024073100', 'dbversion_EtaTmsSync'));

$this->CommitTrans();
