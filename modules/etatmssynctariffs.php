<?php

  class TmsTariff{
    public $db = NULL;
    public $tms_settings = array("host"=>"","login"=>"","password"=>"");
    public $tms_tariffs = array();

    function __construct(){
      global $DB;
      $this->db = $DB;
      $tms_settings_tmp = $this->db->getAll("select * from tms_settings order by id desc limit 1");
      if(count($tms_settings_tmp)<1){
        error_log("Failed to get tms settings");
      } else {
        $this->tms_settings['host'] = $tms_settings_tmp[0]['host'];
        $this->tms_settings['login'] = $tms_settings_tmp[0]['user'];
        $this->tms_settings['password'] = $tms_settings_tmp[0]['passwd'];
      }
    }

    function fetch_tms_tariffs_all(){
      $tms_settings = $this->tms_settings;
      $ch = curl_init($tms_settings['host']."/api/provider/tarifs?limit=0");
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic ".base64_encode($tms_settings['login'].":".$tms_settings['password'])));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($ch);
      curl_close($ch);
      try{
        $response_obj = json_decode($response, true);
        if($response_obj['data']){
          $res = $response_obj['data'];
          // var_dump($res);
        }
      } catch(Exception $e){
        error_log($e);
      }
      return $res;
    }

    function fetch_tms_tariff_by_id($id){
      $tms_settings = $this->tms_settings;
      $ch = curl_init($tms_settings['host']."/api/provider/tarifs/$id");
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic ".base64_encode($tms_settings['login'].":".$tms_settings['password'])));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($ch);
      curl_close($ch);
      try{
        $response_obj = json_decode($response, true);
        if($response_obj['id']){
          $res = $response_obj;
        }
      } catch(Exception $e){
        error_log($e);
      }
      return $res;
    }

    function get_tms_tariff_by_id($id){
      $exists = false;
      $tariff = NULL;
      if($tms_tariffs){
        foreach($tms_tariffs as $k->$v){
          if($v['id'] == $id){
            $tariff = $v;
            $exists = true;
            break;
          }
        }
      }
      if(!$exists){
        $tariff = $this->fetch_tms_tariff_by_id($id);
        if($tariff){
          array_push($this->tms_tariffs, $tariff);
        }
      }
      return $tariff;
    }

    function sync_tms_tariff_link($id, $lms_tariffs){
      $lms_tariffs_str = "";
      $last = $lms_tariffs[count($lms_tariffs)-1];
      foreach($lms_tariffs as $t){
        $lms_tariffs_str .= trim($t['id']);
        if($t != $last){
          $lms_tariffs_str .= ",";
        }
      }
      $sql = "update tms_plans set lmstarif=? where id = ?";
      $this->db->exec($sql, array($lms_tariffs_str, $id));
      if($this->db->GetErrors()){
        throw new Exception(json_encode($this->db->GetErrors()));
      };
    }

    function create_tms_tariff_link($tms_id, $lms_tariffs){
      $res = NULL;
      $lms_tariffs_str = "";
      $last = $lms_tariffs[count($lms_tariffs)-1];
      foreach($lms_tariffs as $t){
        $lms_tariffs_str .= trim($t['id']);
        if($t != $last){
          $lms_tariffs_str .= ",";
        }
      }
      $sql = "insert into tms_plans (tmstarif, lmstarif) values (?,?)";
      $this->db->exec($sql, array($tms_id, $lms_tariffs_str));
      $res = $this->db->GetLastInsertID("tms_plans");
      if($this->db->GetErrors()){
        throw new Exception(json_encode($this->db->GetErrors()));
      };
      return $res;
    }

    function remove_tms_tariff_link($id){
      $sql = "delete from tms_plans where id = ?";
      $this->db->exec($sql, array($id));
      if($this->db->GetErrors()){
        throw new Exception(json_encode($this->db->GetErrors()));
      };
    }

    function get_lms_tariffs_all(){
      $tariffs = $this->db->getAll("select * from tariffs");
      if($this->db->GetErrors()){
        throw new Exception(json_encode($this->db->GetErrors()));
      };
      return $tariffs;
    }

    function get_lms_tariffs($filter){
      if(!$filter){
        return array();
      }
      $params = array();
      $query_str = "%$filter%";
      if(preg_match("\d+")){
        $id_filter = "or id = ?";
        array_push($params, $filter);
      }
      $name_filter = "or lower(name) like lower(?)";
      array_push($params, "%$filter%");
      $sql = "select id, name from tariffs where (1=0 $id_filter $name_filter)";
      $tariffs = $this->db->getAll($sql, $params);
      if(!$tariffs){
        return array();
      }
      if($this->db->GetErrors()){
        throw new Exception(json_encode($this->db->GetErrors()));
      };
      return $tariffs;
    }

    function sync_middleware(){
      $res = EtaTmsSync::runMiddlewareSync();
      return $res;
    }

    function get_tariff_sync(){
      $tariffs = $this->db->getAll("select * from tms_plans order by id desc;");
      $tariffs_sync = array();
      foreach($tariffs as $k=>$v){
        $tms_tariff = $this->get_tms_tariff_by_id($v['tmstarif']);
        if(!$tms_tariff){
          $tms_tariff = array("id"=>$v['tmstariff']);
        }
        $row_tariffs = array("id"=>$v['id'], "tmsTariff"=>$tms_tariff, "lmsTariffs"=>array());
        $lms_tariff_ids = explode(",", $v['lmstarif']); 
        foreach($lms_tariff_ids as $lmsk=>$lmsv){
          if(preg_match("/\d+/",$lmsv)){
            $tariff_name = $this->db->getOne("select name from tariffs where id = ?", array($lmsv));
            array_push($row_tariffs['lmsTariffs'],array("id"=>$lmsv, "name"=>$tariff_name));
          }
        }
        if($this->db->GetErrors()){
          throw new Exception(json_encode($this->db->GetErrors()));
        };
        array_push($tariffs_sync, $row_tariffs);
      }
      return $tariffs_sync;
    }
  }

  $response_obj = array("status"=>"ok", "id"=>"", "message"=>"");

  $tms = new TmsTariff();
  if (isset($_GET['ajax'])){
    $json_str = file_get_contents("php://input");
    $post_data = array();
    if($json_str){
      $post_data = json_decode($json_str,true);
    }
    switch($_GET['ajax']){
      case "lmstariffs":
        if($_GET['q']){
          $tariffs = $tms->get_lms_tariffs($_GET['q']);
        } else {
          $tariffs = $tms->get_lms_tariffs_all();
        }
        echo json_encode($tariffs);
        break;
      case "tmstariffs":
        $tariffs = $tms->fetch_tms_tariffs_all();
        echo json_encode($tariffs);
        break;
      case "syncmiddleware":
        try{
          set_time_limit(600);
          $res = $tms->sync_middleware();
          $response_obj['message'] = $res;
          echo json_encode($response_obj);
        } catch (Exception $e) {
            error_log($e);
            http_response_code(500);
            $response_obj['message'] = "Server error occured";
            $response_obj['status'] = "fail";
            echo json_encode($response_obj);
        }
        break;
      case "commit":
        if($post_data['id']){
          try{
            $tms->sync_tms_tariff_link($post_data['id'], $post_data['lmsTariffs']);
            $response_obj['id'] = $post_data['id'];
            $response_obj['message'] = "updated";
            echo json_encode($response_obj);
          } catch (Exception $e){
            error_log($e);
            http_response_code(500);
            $response_obj['id'] = $post_data['id'];
            $response_obj['message'] = "Server error occured, while updating";
            $response_obj['status'] = "fail";
            echo json_encode($response_obj);
          };
        } else {
          try{
            $res = $tms->create_tms_tariff_link($post_data['tmsID'], $post_data['lmsTariffs']);
            $response_obj['id'] = $res;
            $response_obj['message'] = "Created";
            echo json_encode($response_obj);
          } catch (Exception $e){
            error_log($e);
            http_response_code(500);
            $response_obj['message'] = "Server error occured, while creating";
            $response_obj['status'] = "fail";
            echo json_encode($response_obj);
          };
        }
        break;
      case "delete":
        if($post_data['id']){
          try{
            $tms->remove_tms_tariff_link($post_data['id']);
            $response_obj['id'] = $post_data['id'];
            $response_obj['message'] = "Removed";
            echo json_encode($response_obj);
          } catch (Exception $e){
            error_log($e);
            http_response_code(500);
            $response_obj['message'] = "Server error occured, while removing";
            $response_obj['status'] = "fail";
            echo json_encode($response_obj);
          };
        }
      default:
    }
  } else {
    $tms->tms_tariffs = $tms->fetch_tms_tariffs_all();
    $sync_tariffs = $tms->get_tariff_sync();
    $SMARTY->assign("sync_tariffs", $sync_tariffs);
    $SMARTY->assign("tms_tariffs", $tms->tms_tariffs);
    $SMARTY->display("etatmssynctariffs.html");

  }
?>