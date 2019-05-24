<?php
class EtaTmsSyncAssignmentHandler {
    /**
     * Sets plugin managers
     * @param LMS $hook_data Hook data
     * 
     */

    public function assignmentAdd($hook_data){
        //TODO: sync $hook_data['assignment']['customerid'];
        $cmd = ETATMSBIN
        return $hook_data;
    }

    public function assignmentEdit($hook_data){
        //TODO: sync $hook_data['customerid'];
        return $hook_data;
    }
}
?>
