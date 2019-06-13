<?php

  function update_settings($val){
    global $DB;
    if($val['id']){
      $sql = "UPDATE tms_settings set ";
      $variables = array();
      foreach($val as $k=>$v){
        if($k == "id"){
          continue;
        }
        $sql .= "\"$k\" = ?, ";
        array_push($variables, $v);
      }
      $sql = substr($sql, 0, -2);
      $sql .= " where id = ?";
      array_push($variables, $val['id']);
    } else {
      $keystr = "(";
      $values = "(";
      $variables = array();
      foreach($val as $k=>$v){
        if($k == "id"){
          continue;
        }
        $keystr .= "\"$k\", ";
        $values .= "?,";
        array_push($variables, $v);
      }
      $keystr = substr($keystr, 0, -2);
      $values = substr($values, 0, -1);
      $keystr .= ")";
      $values .= ")";
      $sql = "INSERT INTO tms_settings $keystr values $values";
    }
    // var_dump($sql);
    // var_dump($variables);
    // die();
    $r = $DB->exec($sql, $variables);
    return $r;
  }

  function get_settings(){
    global $DB;
    $settingsRow = $DB->getAll("select * from tms_settings order by id limit 1");
    $settings = [];
    if(count($settingsRow) == 1){
      foreach($settingsRow[0] as $key=>$val){
        $label = "";
        switch($key){
          case "passwd":
            $label = "password";
            break;
          default:
            $label = $key;
            continue;
        }
        if($label != ""){
          $settings[$key] = array("value"=>$val, "label"=>$label);
        }
      }
    } else {
      $settings["id"] = array("value"=>"", "label"=>"id");
      $settings["host"] = array("value"=>"", "label"=>"host");
      $settings["user"] = array("value"=>"", "label"=>"user");
      $settings["passwd"] = array("value"=>"", "label"=>"password");
      $settings["provider"] = array("value"=>"", "label"=>"provider");
    }
    return $settings;
  }

  if (isset($_GET['ajax'])){
  } else if($_SERVER['REQUEST_METHOD'] == "POST"){
    if(isset($_GET['ajax'])){
    } else {
      $ss = update_settings($_POST);
      header("Location: ".$_SERVER['REQUEST_URI']);
    }
  } else {
    $settings = get_settings();
    $SMARTY->assign("settings", $settings);
    $SMARTY->display('etatmssyncconfig.html');
  }
?>