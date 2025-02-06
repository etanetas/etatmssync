<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != "POST") {
  http_response_code(405);
  echo json_encode(array("error" => "Method not allowed"));
  return;
}

if (!isset($_GET['id']) || $_GET['id'] == "") {
  http_response_code(405);
  json_encode(array("error" => "Id is requried"));
  return;
}

try{
  $json = file_get_contents('php://input');
  $data = json_decode($json, true);  
  $link_id = $_GET['id'];

  $tmsSync = new TMSSync();

  $tms_tariff_id = $data['tmsTariff']['id'];
  $lms_tariff_ids = "";

  foreach ($data['lmsTariffs'] as $lmsTariff) {
    $lms_tariff_ids .= $lmsTariff['id'] . ",";
  }

  $lms_tariff_ids = substr($lms_tariff_ids, 0, -1);
  $tmsSync->updateTariffLink($link_id, $tms_tariff_id, $lms_tariff_ids);
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