<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE tms_settings ADD COLUMN sync_stb boolean");
$this->Execute("ALTER TABLE tms_settings ALTER COLUMN sync_stb SET DEFAULT 't'");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024051100', 'dbversion_EtaTmsSync'));

$this->CommitTrans();
