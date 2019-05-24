<?php

class EtaTmsSyncNodeHandler {
    /**
     * Sets plugin managers
     * @param LMS $hook_data Hook data
     * 
     */


    public function nodeAddAfterSubmit($hook_data){
        EtaTmsSync::runCustomerSync($hook_data['nodeadd']['customerid']);
        return $hook_data;
    }

    public function nodeEditAfterSubmit($hook_data){
        EtaTmsSync::runCustomerSync($hook_data['nodeedit']['customerid']);
        return $hook_data;
    }

    public function nodeDelAfterSubmit($hook_data){
        EtaTmsSync::runCustomerSync($hook_data['ownerid']);
        return $hook_data;
    }

    public function nodeSetAfterSubmit($hook_data){
        global $LMS;
        $owner = $LMS->GetNodeOwner($hook_data['nodeid']);
        EtaTmsSync::runCustomerSync($owner);
        return $hook_data;
    }
}
?>
