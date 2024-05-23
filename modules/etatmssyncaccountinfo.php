<?php
  header('Content-Type: application/json');

  try {
    if (!isset($_GET['customerid']) || $_GET['customerid'] == "") {
      http_response_code(500);
      json_encode(array("error" => "Missing customerid"));
      return;
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

    $customer_login = str_replace("%cid",$_GET['customerid'], $tms_settings['login_pattern']);

    $api = new TMSApi($tms_settings['host'], $tms_settings['user'], $tms_settings['passwd'], $tms_settings['provider']);

    $account = $api->getAccountByLogin($customer_login);
    unset($account['pin_md5']);
    echo json_encode($account);

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