<?php

class EtaTmsSyncNodeHandler {

    public function nodeAddAfterSubmit($hook_data){
        EtaTmsSync::runCustomerSync($hook_data['nodeadd']['ownerid']);
        return $hook_data;
    }

    public function nodeEditAfterSubmit($hook_data){
        EtaTmsSync::runCustomerSync($hook_data['nodeedit']['ownerid']);
        return $hook_data;
    }

    public function nodeDelAfterSubmit($hook_data){
        EtaTmsSync::runCustomerSync($hook_data['ownerid']);
        return $hook_data;
    }

    public function nodeSetAfterSubmit($hook_data){
        global $LMS;
        if($hook_data['nodeid']){
            $owner = $LMS->GetNodeOwner($hook_data['nodeid']);
            EtaTmsSync::runCustomerSync($owner);
        } else if($hook_data['nodes']){
            $owners = array();
            foreach($hook_data['nodes'] as $node){
                $owner = $LMS->GetNodeOwner($node);
                if(!in_array($owner, $owners)){
                    array_push($owners, $owner);
                }
            }
            foreach($owners as $owner){
                EtaTmsSync::runCustomerSync($owner);
            }
        } 
        return $hook_data;
    }
}
?>
