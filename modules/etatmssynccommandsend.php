<?php

  try {
    if ($_SERVER['REQUEST_METHOD'] != "POST") {
      http_response_code(405);
      echo json_encode(array("error" => "Method not allowed"));
      return;
    }

    $json = file_get_contents('php://input');

    $data = json_decode($json, true);  

    if (json_last_error() != JSON_ERROR_NONE) {
      throw new Exception("Bad JSON: " . json_last_error_msg());
    } 

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

    $api = new TMSApi($tms_settings[0]['host'], $tms_settings[0]['user'], $tms_settings[0]['passwd'], $tms_settings[0]['provider']);
    echo json_encode(array("status" => "ok"));
  } catch (TMSApiException $e)  {
    if ($e->getCode() != 0) {
      http_response_code($e->getCode());
    } else {
      http_response_code(500); 
    }
    echo json_encode(array("error" => $e->getMessage()));
  } catch (Exception $e){
    http_response_code(500);
    $err = array(
      "error" => "Server error",
      "detail" => $e->getMessage()
    );
    echo json_encode($err);
  }
?>