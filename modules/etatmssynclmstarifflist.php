<?php
  header('Content-Type: application/json');

  try {
    $tmsSync = new TMSSync();
    $name = "";
    if (isset($_GET['name']) && $_GET['name'] != "") {
      $name = trim($_GET['name']);
    } 
    $exclude = "";
    if (isset($_GET['exclude']) && $_GET['exclude'] != "") {
      $exclude = trim($_GET['exclude']);
    }
    $tariffs = $tmsSync->getLmsTariffs($name, $exclude); 
    if ($tariffs == NULL){
      $tariffs = array();
    }
    echo json_encode($tariffs);
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