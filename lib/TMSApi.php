<?php

class TMSApi {

  function __construct($url, $username, $password, $provider){
    if (!$url){
      throw new Exception("TMS URL not set");
    }

    if (!$username){
      throw new Exception("TMS Username not set");
    }

    if (!$password){
      throw new Exception("TMS password not set");
    }

    $this->username = $username;
    $this->password = $password;
    $this->url = $url;
    $this->provider = $provider;
  }

  private function get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Authorization: Basic ".base64_encode($this->username.":".$this->password), 
      "Content-Type: application/json"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response == false) {
      throw new TMSApiException("Failed to connect to TMS", 500);
    }


    if ($http_code != 200) {
      throw new TMSApiException($response, $http_code);
    } 

    return($response);
  }

  private function post($url, $data="") {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Optional: Disable SSL verification (use with caution)

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Authorization: Basic ".base64_encode($this->username.":".$this->password),
      "Content-Type: application/json" // Set the content type for JSON data (adjust as needed)
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true); // Set the request method to POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Send data as JSON (adjust based on data format)

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response == false) {
      throw new TMSApiException("Failed to connect to TMS", 500);
    }


    if ($http_code != 200) {
      throw new TMSApiException($response, $http_code);
    } 

    return($response);
  }

  private function put($url, $data="") {
    throw new TMSApiException("Not tested");
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Optional: Disable SSL verification (use with caution)

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Authorization: Basic ".base64_encode($this->username.":".$this->password),
      "Content-Type: application/json" // Set the content type for JSON data (adjust as needed)
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_PUT, true); // Set the request method to PUT
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Send data as JSON (adjust based on data format)

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
  } 

  private function delete($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Authorization: Basic ".base64_encode($this->username.":".$this->password), 
      "Content-Type: application/json"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
      throw new TMSApiException($response, $http_code);
    } 

    return($response);
  } 

  public function getAccountByLogin($login){
    $url = $this->url."/api/provider/accounts?provider=$id&login=$login";
    $data = json_decode($this->get($url), true);
    if ($data['data'] && count($data['data']) > 0) {
      $account = $data['data'][0];
    }
    return $account;
  } 

  public function createAccount($account_data){
    throw(new Exception("Not implemented"));
    $resp = $this->post($this->url."/api/provider/accounts", $account_data);
  }

  public function  getDevicesByAccount($account_id){
    $devices = array();
    $url = $this->url."/api/provider/devices?account=$account_id";
    $data = json_decode($this->get($url), true);
    if ($data['data']) {
      $devices = $data['data'];
    }
    return $devices;
  }

  public function  deleteDeviceByID($id){
    $devices = array();
    $url = $this->url."/api/provider/devices/$id";
    $resp = json_decode($this->delete($url), true);
    return $resp;
  }

  public function getTariffs( $name="", $exclude_ids=array()){
    $tariffs = array();
    $url = $this->url."/api/provider/tarifs?limit=0&start=0";
    if ($name != ""){
      $url .= "&quick_search=$name";
    }

    $data = json_decode($this->get($url), true);
    if ($data['data']) {
      foreach($data['data'] as $tariff){
        if (!in_array($tariff['id'], $exclude_ids)){
          array_push($tariffs, $tariff);
        }
      } 
    }
    return $tariffs;
  }

  public function restartDevices($device_ids){
    $command = array(
      "command"=> "restart"
    );
    $this->sendCommand($device_ids, $command);
  }
  
  public function refreshDevicesChannelList($device_ids){
    $command = array(
      "command"=> "refresh_channel_list"
    );
    $this->sendCommand($device_ids, $command);
  }

  public function sendMessage($device_ids, $type, $title, $text){
    if (count($device_ids) < 1) {
      return;
    }

    if ( $type !=  "notify" && $type != "confirm" && $type != "ticker") {
      throw new Exception("Unknown message type");
    }

    $command = array(
      'command' => 'user_message',
      'type' => $type,
      'title' => $title,
      'text' => $text
    );
    $res = $this->sendCommand($device_ids, $command);
  }

  public function sendCommand($device_ids, $command){
    $url = $this->url."/api/provider/commands/send/devices?broadcast=false";
    $data = array (
      "ids" => $device_ids,
      "commands" => array( $command )
    );
    $res = $this->post($url, $data);
  }

}

class TMSApiException extends Exception {
  public function __construct($message, $code = 0) {
    parent::__construct($message, $code);
  }
}  

?>