<?php

/**
 * Tms Sync Plugin
 *
 * @author Ksistof Vinco <ksistof.vinco@gmail.com>
 * @author Darius Urbonas
 */


class EtaTmsSync extends LMSPlugin {
	const PLUGIN_NAME = 'Tms Sync';
	const PLUGIN_DESCRIPTION = 'Etanetas plugin for lms->tms synchronization';
	const PLUGIN_AUTHOR = 'Ksistof Vinco, Darius Urbonas';

    public function registerHandlers()
    {

        $configfile = file_exists(getcwd().DIRECTORY_SEPARATOR.'lms.ini') ? getcwd().DIRECTORY_SEPARATOR.'lms.ini' : "/etc/lms/lms.ini";

        define('ETATMSBIN', dirname(__FILE__). DIRECTORY_SEPARATOR .'bin' . DIRECTORY_SEPARATOR . "sync -c $configfile");

        $this->handlers = array(
            'access_table_initialized' => array(
                'class' => 'EtaTmsSyncInitHandler',
                'method' => 'accessTableInit'
            ),
            'customeredit_after_submit' => array(
                'class' => 'EtaTmsSyncCustomerHandler',
                'method' => 'customerEditAfterSubmit'
            ),
            'customeradd_after_submit' => array(
                'class' => 'EtaTmsSyncCustomerHandler',
                'method' => 'customerAddAfterSubmit'
            ),
            'customerdel_after_submit' => array(
                'class' => 'EtaTmsSyncCustomerHandler',
                'method' => 'customerDeleteAfterSubmit'
            ),
            'customerassignmentadd_after_submit' => array(
                'class' => 'EtaTmsSyncAssignmentHandler',
                'method' => 'assignmentAdd'
            ),
            'customerassignmentedit_after_submit' => array(
                'class' => 'EtaTmsSyncAssignmentHandler',
                'method' => 'assignmentEdit'
            ),
            'nodeset_after_submit' => array(
                'class' => 'EtaTmsSyncNodeHandler',
                'method' => 'nodeSetAfterSubmit'
            ),
            'nodeadd_after_submit' => array(
                'class' => 'EtaTmsSyncNodeHandler',
                'method' => 'nodeAddAfterSubmit'
            ),
            'nodeedit_after_submit' => array(
                'class' => 'EtaTmsSyncNodeHandler',
                'method' => 'nodeEditAfterSubmit'
            ),
            'nodedel_after_submit' => array(
                'class' => 'EtaTmsSyncNodeHandler',
                'method' => 'nodeDelAfterSubmit'
            )
        );
    }
}

