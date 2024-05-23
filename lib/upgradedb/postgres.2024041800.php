<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE tms_settings ADD COLUMN login_pattern varchar(100)");
$this->Execute("ALTER TABLE tms_settings ALTER COLUMN login_pattern SET DEFAULT 'lms_%cid'");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024041800', 'dbversion_EtaTmsSync'));

$this->CommitTrans();
