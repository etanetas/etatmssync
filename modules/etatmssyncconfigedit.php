<?php
  if ($_SERVER['REQUEST_METHOD'] != "POST") {
    http_response_code(405);
    echo json_encode(array("error" => "Method not allowed"));
    return;
  }

  if (!isset($_GET['id']) || $_GET['id'] == "") {
    http_response_code(500);
    json_encode(array("error" => "Id is requried"));
    return;
  }

  try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);  
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception("Bad JSON: " . json_last_error_msg());
    }
    $id = $_GET['id'];

    if (!isset($data['host']) || $data['host'] == "") {
      http_response_code(405);
      json_encode(array("error" => "Host is requried"));
      return;
    }
    if (!isset($data['user']) || $data['user'] == "") {
      http_response_code(405);
      json_encode(array("error" => "User is requried"));
      return;
    }
    if (!isset($data['passwd']) || $data['passwd'] == "") {
      http_response_code(405);
      json_encode(array("error" => "Password is requried"));
      return;
    }
    if (!isset($data['provider']) || $data['provider'] == "") {
      http_response_code(405);
      json_encode(array("error" => "Provider is requried"));
      return;
    }
    if (!isset($data['login_pattern']) || $data['login_pattern'] == "") {
      http_response_code(405);
      json_encode(array("error" => "Login pattern is requried"));
      return;
    }

    if (!isset($data['sync_stb'])) {
      $data['sync_stb'] = true;
    }

    if (!isset($data['additional_devices'])) {
      $data['additional_devices'] = -1;
    }

    $tmsSync = new TMSSync();
    $tmsSync->updateSettings($id, $data['host'], $data['user'], $data['passwd'], $data['provider'], $data['login_pattern'], $data['sync_stb'], $data['additional_devices']);
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