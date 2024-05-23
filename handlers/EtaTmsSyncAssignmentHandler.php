<?php

class EtaTmsSyncAssignmentHandler {

    public function assignmentAdd($hook_data){
        EtaTmsSync::runCustomerSync($hook_data['assignment']['customerid']);
        return $hook_data;
    }

    public function assignmentEdit($hook_data){
        EtaTmsSync::runCustomerSync($hook_data['a']['customerid']);
        return $hook_data;
    }

    public function assginmentDel($hook_data){
        return $hook_data;
    }
}
?>
