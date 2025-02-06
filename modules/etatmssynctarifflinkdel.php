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

  $link_id = $_GET['id'];

  $tmsSync = new TMSSync();
  $tmsSync->deleteTariffLink($link_id);
  echo json_encode(array("message" => "ok"));

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

?>