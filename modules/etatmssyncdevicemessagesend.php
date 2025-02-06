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
    throw new Exception('Error decoding JSON: ' . json_last_error_msg());
  }

  if (!isset($data['devices']) || count($data['devices']) == 0) {
    throw new Exception('Devices not specified');
  }

  $tmsSync = new TMSSync();
  $tms_settings = $tmsSync->getSettings();

  if ($tms_settings == NULL){
    if (!$tms_settings || count($tms_settings) < 1) {
      throw new Exception('Failed to get tms settings');
    }
  }

  $api = new TMSApi($tms_settings['host'], $tms_settings['user'], $tms_settings['passwd'], $tms_settings['provider']);
  $api->sendMessage($data['devices'], $data['type'], $data['title'], $data['message']);

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
  error_log("ETATMSSYNC Error: $e");
  $err = array(
    "error" => "Server error",
    "detail" => $e->getMessage()
  );
  http_response_code(500);
  echo json_encode($err);
}