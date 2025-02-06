<?php
  if (!isset($_GET['accountid']) || $_GET['accountid'] == "") {
    http_response_code(500);
    echo json_encode(array('error' => 'Account id is requried'));
    return;
  }

  try {
    $tmsSync = new TMSSync();
    $tms_settings = $tmsSync->getSettings();
    if ($tms_settings == NULL){
      if (!$tms_settings || count($tms_settings) < 1) {
        error_log("ETATMSSYNC Error: Failed to get tms settings");
        http_response_code(500);
        json_encode(array("error" => "Failed to get tms settings"));
        return;
      }
    }

    $api = new TMSApi($tms_settings['host'], $tms_settings['user'], $tms_settings['passwd'], $tms_settings['provider']);

    $devices = $api->getDevicesByAccount($_GET['accountid']);
    echo json_encode($devices);
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