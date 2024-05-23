<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE tms_plans ALTER COLUMN tmstarif TYPE integer USING (tmstarif::integer)");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024042400', 'dbversion_EtaTmsSync'));

$this->CommitTrans();
