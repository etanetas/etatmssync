<?php

if ($_SERVER['REQUEST_METHOD'] != "POST") {
  http_response_code(405);
  echo json_encode(array("error" => "Method not allowed"));
  return;
}

try {
  $json = file_get_contents('php://input');

  $data = json_decode($json, true);  

  if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("Bad JSON: " . json_last_error_msg());
  }

  if (!isset($data['host']) || $data['host'] == "") {
    http_response_code(500);
    echo json_encode(array("error" => "Host is requried"));
    return;
  }
  if (!isset($data['user']) || $data['user'] == "") {
    http_response_code(500);
    echo json_encode(array("error" => "User is requried"));
    return;
  }
  if (!isset($data['passwd']) || $data['passwd'] == "") {
    http_response_code(500);
    echo json_encode(array("error" => "Password is requried"));
    return;
  }
  if (!isset($data['provider']) || $data['provider'] == "") {
    http_response_code(500);
    echo json_encode(array("error" => "Provider is requried"));
    return;
  }
  if (!isset($data['login_pattern']) || $data['login_pattern'] == "") {
    $data['login_pattern'] = "lms_%cid";
  }

  if (!isset($data['sync_stb'])) {
    $data['login_pattern'] = true;
  }

  $tmsSync = new TMSSync();
  $tmsSync->insertSetting($data['host'], $data['user'], $data['passwd'], $data['provider'], $data['login_pattern'], $data['sync_stb']);
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