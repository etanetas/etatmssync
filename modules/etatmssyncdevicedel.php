<?php

  if ($_SERVER['REQUEST_METHOD'] != "POST") { 
    http_response_code(405);
    echo json_encode(array("error" => "Request method not allowed"));
    return;
  }

  if (!isset($_GET['id']) || $_GET['id'] == "") {
    http_response_code(500);
    echo json_encode(array("error" => "id is requried"));
    return;
  }

  try {
    $tmsSync = new TMSSync();
    $tms_settings = $tmsSync->getSettings();
    if ($tms_settings == NULL){
      if (!$tms_settings || count($tms_settings) < 1) {
        error_log("Failed to get tms settings");
        http_response_code(500);
        json_encode(array("error" => "Failed to get tms settings"));
        return;
      }
    }
    $api = new TMSApi($tms_settings['host'], $tms_settings['user'], $tms_settings['passwd'], $tms_settings['provider']);
    $api->deleteDeviceByID($_GET['id']);
    echo json_encode(array("status" => "ok"));
  } catch (TMSApiException $e)  {
    if ($e->getCode() != 0) {
      http_response_code($e->getCode());
    } else {
      http_response_code(500); 
    }
    echo json_encode(array("error" => $e->getMessage()));
  } catch (Exception $e){
    error_log("ETATMSSYNC Error: $e");
    $err = array(
      "error" => "Server error",
      "detail" => $e->getMessage()
    );
    http_response_code(500);
    echo json_encode($err);
  }

?>