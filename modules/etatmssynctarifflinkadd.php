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

  if (json_last_error() != JSON_ERROR_NONE) {
    throw new Exception("Bad JSON: " . json_last_error_msg());
  } 

  $tmsSync = new TMSSync();

  $tms_tariff_id = $data['tmsTariff']['id'];
  $lms_tariff_ids = "";

  foreach ($data['lmsTariffs'] as $lmsTariff) {
    $lms_tariff_ids .= $lmsTariff['id'] . ",";
  }

  $lms_tariff_ids = substr($lms_tariff_ids, 0, -1);

  $id = $tmsSync->insertTariffLink($tms_tariff_id, $lms_tariff_ids);

  http_response_code(201);
} catch (TMSApiException $e)  {
  if ($e->getCode() != 0) {
    http_response_code($e->getCode());
  } else {
    http_response_code(500); 
  }
  echo json_encode(array("error" => $e->getMessage()));
  return;
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
?>