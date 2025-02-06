<?php
 try {
    $tmsSync = new TMSSync();
    $tms_settings = $tmsSync->getSettings();
    if ($tms_settings == NULL){
      if (!$tms_settings || count($tms_settings) < 1) {
        error_log("Failed to get tms settings");
        http_response_code(500);
        json_encode(array("error" => "Server error"));
        return;
      }
    }
    $name = "";

    if(isset($_GET['name']) && $_GET['name'] != ""){
      $name = $_GET['name'];
    }

    $links = $tmsSync->getTariffLinks();
    $exclude_tms_ids = array();

    foreach($links as $link){
      array_push($exclude_tms_ids, $link['tmstarif']);
    }

    $api = new TMSApi($tms_settings['host'], $tms_settings['user'], $tms_settings['passwd'], $tms_settings['provider']);
    $tariffs = $api->getTariffs($name, $exclude_tms_ids);
    echo json_encode($tariffs);

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