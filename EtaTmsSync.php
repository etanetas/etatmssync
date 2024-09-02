<?php

/**
 * Tms Sync Plugin
 *
 * @author Ksistof Vinco <ksistof.vinco@gmail.com>
 */


class EtaTmsSync extends LMSPlugin {
    const PLUGIN_DIRECTORY_NAME = 'EtaTmsSync';
	const PLUGIN_NAME = 'Tms Sync';
	const PLUGIN_DESCRIPTION = 'Etanetas plugin for lms->tms synchronization';
    const PLUGIN_AUTHOR = 'Ksistof Vinco';
    const PLUGIN_DB_VERSION = '2024073100';

    public static $plugindir = null;
    
    public function registerHandlers(){
        EtaTmsSync::$plugindir = dirname(__FILE__);

        $configfile = file_exists(getcwd().DIRECTORY_SEPARATOR.'lms.ini') ? getcwd().DIRECTORY_SEPARATOR.'lms.ini' : "/etc/lms/lms.ini";

        define('ETATMSBINSTDOUT', "export LANG=en_US.UTF-8 && " . dirname(__FILE__). DIRECTORY_SEPARATOR .'bin' . DIRECTORY_SEPARATOR. "tms_sync".DIRECTORY_SEPARATOR."sync.py");
        define('ETATMSBIN', "export LANG=en_US.UTF-8 && " . dirname(__FILE__). DIRECTORY_SEPARATOR .'bin' . DIRECTORY_SEPARATOR . "tms_sync".DIRECTORY_SEPARATOR."sync.py -c $configfile %s > /dev/null 2>&1 &");

        $this->handlers = array(
            'access_table_initialized' => array(
                'class' => 'EtaTmsSyncInitHandler',
                'method' => 'accessTableInit'
            ),
            'menu_initialized' => array(
                'class' => 'EtaTmsSyncInitHandler',
                'method' => 'menuInit'
            ),
			'modules_dir_initialized' => array(
				'class' => 'EtaTmsSyncInitHandler',
				'method' => 'modulesDirInit'
            ),
            'smarty_initialized' => array(
                'class' => 'EtaTmsSyncInitHandler',
                'method' => 'smartyInit'
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
            'customerassignmentdel_after_submit' => array(
                'class' => 'EtaTmsSyncAssignmentHandler',
                'method' => 'assignmentDel'
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

    public static function runMiddlewareSync(){
        $return_code = -1;
        $out = [];
        $cmd= ETATMSBINSTDOUT;
        exec($cmd, $out, $return_code);
        if($return_code != 0){
            error_log("ETATMSSYNC Error: Failed to run $cmd,\n returned code: $return_code,\n output: ".implode(" ", $out));
            throw new Exception($out);
        }
        return implode($cmd);
    }

    public static function runCustomerSync($customerid){
        try{
            $return_code = -1;
            $out = [];
            $cmd = "";
            if($customerid){
                $cmd = sprintf(ETATMSBIN," -s $customerid");
                $ss = exec($cmd, $out, $return_code);
              
                /*error_log("cmd: $cmd, return_code: $return_code, out: ".implode(" ", $out));*/
                if($return_code != 0){
                    error_log("ETATMSSYNC Error: Failed to run $cmd,\n returned code: $return_code,\n output: ".implode(" ", $out));
                }
            } else {
                error_log("ETATMSSYNC Warning: runCustomerSync customer not set");
            }
        } catch (Exception $e){
            error_log("ETATMSSYNC Error: $e");
        }
    }
}
