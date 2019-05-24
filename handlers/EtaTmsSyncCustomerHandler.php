<?php
class EtaTmsSyncCustomerHandler {
  /**
   * Sets plugin managers
   *
   * @param LMS $hook_data Hook data
   */

  public function customerEditAfterSubmit(array $hook_data = array()){
    //TODO: sync $hook_data['customerdata']['id'];
    return $hook_data;
  }

  public function customerAddAfterSubmit(array $hook_data = array()){
    //TODO: sync $hook_data['id'];
    return $hook_data;
  }

  public function customerDeleteAfterSubmit($hook_data){
    //TODO: sync $hook_data['id'];
    return $hook_data;
  }

}
?>
