<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] != "POST") {
  http_response_code(405);
  echo json_encode(array("error" => "Method not allowed"));
  return;
}
try{
  $json = file_get_contents('php://input');
  $data = json_decode($json, true);  

  if (json_last_error() !== JSON_ERROR_NONE) {
  }

  if (!isset($data['devices']) || count($data['devices']) == 0) {
    throw new Exception('Devices not specified');
  }

  $tmsSync = new TMSSync();
  $tms_settings = $tmsSync->getSettings();
  if ($tms_settings == NULL){
    if (!$tms_settings || count($tms_settings) < 1) {
      error_log("Failed to get tms settings");
      http_response_code(500);
      echo json_encode(array("error" => "Failed to get tms settings"));
      return;
    }
  }

  $api = new TMSApi($tms_settings['host'], $tms_settings['user'], $tms_settings['passwd'], $tms_settings['provider']);
  $api->restartDevices($data['devices']);

  echo json_encode(array("status"=> "ok"));
} catch (TMSApiException $e)  {
  if ($e->getCode() != 0) {
    http_response_code($e->getCode());
  } else {
    http_response_code(500); 
  }
  echo json_encode(array("error" => $e->getMessage()));
} catch (Exception $e){
  $err = array(
    "error" => "Server error",
    "detail" => $e->getMessage()
  );
  http_response_code(500);
  echo json_encode($err);
} catch (Error $e) {
  $err = array(
    "error" => "Server error",
    "detail" => $e->getMessage()
  );
  http_response_code(500);
  echo json_encode($err);
}