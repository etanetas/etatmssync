<?php
  try {
    $tmsSync = new TMSSync();
    $settings = $tmsSync->getSettings();

    if ($settings == NULL){
      $settings = array(
        "id" => null,
        "host" => "",
        "user" => "",
        "passwd" => "",
        "provider" => null,
        "login_pattern" => "lms_%cid"
      );
    }

    echo json_encode($settings);
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