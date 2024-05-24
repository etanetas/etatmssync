<?php
header('Content-Type: application/json');

function filterTmsTariffByID($tmsTariffs, $id) {
  foreach($tmsTariffs as &$tmsTariff) {
    if($tmsTariff['id'] == $id) {
      return $tmsTariff;
    }
  }
}

try{


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
  $tmsTariffs = $api->getTariffs();
  $tariffLinks = $tmsSync->getTariffLinks();
  if (!$tariffLinks || count($tariffLinks) < 1) {
    $tariffLinks = array();
  }

  foreach($tariffLinks as &$link) {
    $lmsTariffIds = explode(",", $link['lmstarif']);
    $link['lmsTariffs'] = $tmsSync->getLmsTariffsByIDs($lmsTariffIds);
    $link['tmsTariff'] = filterTmsTariffByID($tmsTariffs, $link['tmstarif']);
  }

  echo json_encode($tariffLinks);
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

?>