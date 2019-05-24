<?php
class EtaTmsSyncNodeHandler {
    /**
     * Sets plugin managers
     * @param LMS $hook_data Hook data
     * 
     */

    public function nodeAddAfterSubmit($hook_data){
        //TODO sync $hook_data['nodeadd']['customerid'];
        return $hook_data;
    }

    public function nodeEditAfterSubmit($hook_data){
        //TODO sync $hook_data['nodeedit']['customerid'];
        return $hook_data;
    }

    public function nodeDelAfterSubmit($hook_data){
        //TODO sync $hook_data['ownerid'];
        return $hook_data;
    }

    public function nodeSetAfterSubmit($hook_data){
        //TODO sync $hook_data['nodeid'];
        return $hook_data //????
    }
}
?>
