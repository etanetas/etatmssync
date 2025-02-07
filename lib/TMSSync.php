<?php
  class TMSSync {
    private $db;

    public function __construct(){
        $this->db = LMSDB::getInstance();
    }

    public function getSettings(){
      $settings = $this->db->getAll("SELECT * FROM tms_settings ORDER BY id DESC LIMIT 1");
      $errors = $this->db->GetErrors();
      if ($errors){
        $error_msg = "DB Errors:";
        foreach($this->db->GetErrors() as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      }
      if(!$settings || count($settings)<1){
        return;
      } else {
        if ($settings[0]['sync_stb'] == "t"){
          $settings[0]['sync_stb'] = true;
        } else {
          $settings[0]['sync_stb'] = false;
        }
        return $settings[0];
      }
    }

    private function validateLoginPattern($login_pattern){
      if (strpos($login_pattern, "%cid") === false){
        throw new Exception("Login pattern incorrect. Must contain '%cid'");
      }
    }

    public function updateSettings($id, $host, $user, $password, $provider, $login_pattern, $sync_stb, $additional_devices){
      $this->validateLoginPattern($login_pattern);
      if ($sync_stb == true) {
        $sync_stb = 't';
      } else {
        $sync_stb = 'f';
      }
      $query = "UPDATE tms_settings SET \"host\" = ?, \"user\" = ?, \"passwd\" = ?, \"provider\" = ?, \"login_pattern\" = ?, \"sync_stb\" = ?, \"additional_devices\" = ? where id = ?";
      $params = array($host, $user, $password, $provider, $login_pattern, $sync_stb, $additional_devices, $id);
      $res = $this->db->Exec($query, $params);
      $errors = $this->db->GetErrors();
      if ($errors){
        $error_msg = "DB Errors:";
        foreach($this->db->GetErrors() as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      }
      return $res;
    }

    public function insertSetting($host, $user, $password, $provider, $login_pattern, $sync_stb, $additional_devices){
      $this->validateLoginPattern($login_pattern);
      if ($sync_stb == true) {
        $sync_stb = 't';
      } else {
        $sync_stb = 'f';
      }
      $query = 'INSERT INTO tms_settings ("host","user","passwd","provider","login_pattern","sync_stb", "additional_devices") VALUES (?,?,?,?,?,?,?)';
      $res = $this->db->Exec($query, array($host, $user, $password, $provider, $login_pattern, $sync_stb, $additional_devices));
      $errors = $this->db->GetErrors();
      if ($errors) {
        $error_msg = "DB Errors:";
        foreach($errors as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      }
      return $res;
    }

    function getLmsTariffsByIDs($ids){
      $in = str_repeat('?,', count($ids) - 1) . '?';
      $tariffs = $this->db->getAll("select id, name, description from tariffs where id in ($in)", $ids);
      $errors = $this->db->GetErrors();
      if($errors){
        $error_msg = "DB Errors:";
        foreach($errors as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      };
      return $tariffs;
    }

    function getLmsTariffs($name="", $exclude=""){
      $sql = "select id, name, description from tariffs where 1=1";
      $filter = [];
      if (trim($name) != "") {
        $sql .= " and lower(name) like lower(?)";
        array_push($filter, "%$name%");
      }
      if ($exclude) {
        if (!preg_match("/^\d+(,\d+)*$/", $exclude)) {
          throw new Exception("Invalid exclude parameter");
        }
        $exclude_ids = explode(",", $exclude);
        $ids = array_map('intval', $exclude_ids);
        $sql .= " and id not in (".implode(",", $ids).")";
      }
      $tariffs = $this->db->getAll($sql, $filter);
      $errors = $this->db->GetErrors();
      if($errors){
        $error_msg = "DB Errors:";
        foreach($errors as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      };
      return $tariffs;
    }

    function getTariffLinks(){
      $tariffLinks = $this->db->getAll("select * from tms_plans order by id desc");
      $errors = $this->db->GetErrors();
      if ($errors){
        $error_msg = "DB Errors:";
        foreach($errors as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      }
      if ($tariffLinks == NULL){
        $tariffLinks = [];
      }
      return $tariffLinks;
    }

    function getTariffLinkByID($id){ 
      $tariffLinks = $this->db->getAll("select * from tms_plans where id = ?", array($id));
      $errors = $this->db->GetErrors();
      if ($errors){
        $error_msg = "DB Errors:";
        foreach($errors as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      }
      return $tariffLinks;
    }

    function insertTariffLink($tms_id, $lms_tariffs){
      if (!$tms_id) {
        throw new Exception("Invalid tms_id");
      }

      if (!$lms_tariffs) {
        throw new Exception("Invalid lms_tariffs");
      }

      $query = "insert into tms_plans (tmstarif, lmstarif) values (?,?) RETURNING id";
      $res = $this->db->Exec($query, array($tms_id, $lms_tariffs));
      $errors = $this->db->GetErrors();
      if ($errors){
        $error_msg = "DB Errors:";
        foreach($errors as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      }
      $id = $this->db->GetLastInsertID("tms_plans");
      return $id;
    }

    function updateTariffLink($link_id, $tms_id, $lms_tariffs){
      $query = "update tms_plans set tmstarif=?, lmstarif=? where id = ?";
      $res = $this->db->Exec($query, array($tms_id, $lms_tariffs, $link_id));
      $errors = $this->db->GetErrors();
      if ($errors){
        $error_msg = "DB Errors:";
        foreach($errors as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      }
    }

    function deleteTariffLink($link_id){
      $query = "delete from tms_plans where id = ?";
      $res = $this->db->Exec($query, array($link_id));
      $errors = $this->db->GetErrors();
      if ($errors){
        $error_msg = "DB Errors:";
        foreach($errors as $err){
          $error_msg .= $err['error']."\n";
        }
        throw new Exception($error_msg);
      }
    }

  }
?>
