<?php
  function isPhoneNum($num) {
    return preg_match("/^\+[0-9]{3} [0-9]?[0-9]?[0-9]?[0-9]?[0-9]?[0-9]?[0-9]?[0-9]?[0-9]?[0-9]?[0-9]?[0-9]?[0-9]?$/", $num);
  }

  function splitNumber($num) {
    $splitted = explode(" ",$num);
    $country = substr($splitted[0], 1);
    $splitted[0] = "";
    $number = substr(implode(" ", $splitted),1);
    return array($country, $number);
  }


  include "debug.php";
  require "mysql.php";
  $time = time();
  $mysql_connection = mysqli_connect($mysql_ip, $mysql_user, $mysql_pass, $mysql_db);
  mysqli_query($mysql_connection, "INSERT INTO `api_requests` (sent_when, request_path, ip)
  VALUES('".$time."', '".mysqli_real_escape_string($mysql_connection, $_SERVER['REQUEST_URI'])."', '".$_SERVER['REMOTE_ADDR']."')");
  header("Content-Type: application/json");
  $response = array();
  $bhjHFdyt = mysqli_query($mysql_connection, "SELECT * FROM `api_requests` WHERE sent_when >= '".($time-300)."' AND ip='".$_SERVER['REMOTE_ADDR']."'");
  $req_count = mysqli_num_rows($bhjHFdyt);
  $fiUyhdrt = mysqli_query($mysql_connection, "SELECT * FROM `blacklisted_ips` WHERE `ip`='$_SERVER[REMOTE_ADDR]'");
  $GfytFjkJ = mysqli_num_rows($fiUyhdrt);
  if($GfytFjkJ > 0) {
    $response = array('success' => false, 'error' => 'Your IP has been blacklisted.');
    echo json_encode($response);
    return;
  }
  $uyFKufHb = mysqli_query($mysql_connection, "SELECT * FROM `api_limit_bypass` WHERE ip='$_SERVER[REMOTE_ADDR]'");
  $uTfjkmvj = mysqli_num_rows($uyFKufHb);
  if($req_count > 7 && $uTfjkmvj == 0) {
    $response = array('success' => false, 'error' => 'You\'ve reached the max requests in 5 minutes. Try again later.');
    echo json_encode($response);
    return;
  }
  $ip = $_SERVER['REMOTE_ADDR'];
  if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER["HTTP_X_FORWARDED_FOR"]." / ".$ip;
  }
  if(isset($_GET['req'])) {
    $req = strtolower($_GET['req']);
    if($req == "add") {
      $data = "";
      $specifiedname = "N/A";

      if(isset($_GET['specifiedname'])) {
        $specifiedname = $_GET['specifiedname'];
      }
      if(isset($_GET['data']) && isPhoneNum($_GET['data'])) {
        $splitted = splitNumber($_GET['data']);
        $data = $_GET['data'];
        $q = mysqli_query($mysql_connection, "INSERT INTO `scam_numbers` (country, number, submitted_ip, submitted_name)
        VALUES ('".$splitted[0]."', '".$splitted[1]."', '".mysqli_real_escape_string($mysql_connection, $ip)."', '".mysqli_real_escape_string($mysql_connection, $specifiedname)."')");
        if(!$q) {
          $response = array('success' => false, 'error' => 'SQL Error; Summary: '.mysqli_error($mysql_connection));
        } else {
          $response = array('success' => true, 'summary' => array('country' => $splitted[0], 'number' => $splitted[1], 'ip' => $ip, 'name' => $specifiedname));
        }
      } else if (!isset($_GET['data'])){
        $response = array('success' => false, 'error' => 'No data was given');
      } else {
        $response = array('success' => false, 'error' => 'Invalid phone number: '.$_GET['data']);
      }
    } else if($req == "get") {
      $limit = 10;
      if(isset($_GET['limit'])) {
        $limit = intval($_GET['limit']);
      }
      $q = mysqli_query($mysql_connection, "SELECT id,country,number,submitted_name,submitted_date FROM `scam_numbers` WHERE approved=1 ORDER BY id DESC LIMIT ".$limit);
      $results = array(); // make a new array to hold all your data
      $index = 0;
      while($row = mysqli_fetch_assoc($q)){ // loop to store the data in an associative array.
           $results[$index] = $row;
           $index++;
      }
      if(!$q) {
        $response = array('success' => false, 'error' => 'SQL Error; Summary: '.mysqli_error($mysql_connection));
      } else {
        $response = array('success' => true, 'summary' => array('limit' => $limit), 'data' => $results);
      }
    } else if($req == "report") {
      if(isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $q1 = mysqli_query($mysql_connection, "SELECT * FROM `scam_numbers` WHERE id='$id' AND approved='1'");
        $q1rows = mysqli_num_rows($q1);
        if($q1rows > 0) {
          $escapedip = mysqli_real_escape_string($mysql_connection, $ip);
          $q2 = mysqli_query($mysql_connection, "SELECT * FROM `not_working_reports` WHERE number_id='$id' AND ip='$escapedip'");
          $q2rows = mysqli_num_rows($q2);
          if($q2rows == 0) {
            mysqli_query($mysql_connection, "INSERT INTO `not_working_reports` (ip, report_type, number_id)
            VALUES('$escapedip', 'numbers', '$id')");
            $response = array('success' => true, 'summary' => array('ip' => $ip, 'type' => 'numbers', 'id' => $id));
          } else {
            $response = array('success' => false, 'error' => 'You cant report the same number twice');
          }
        } else {
          $response = array('success' => false, 'error' => 'Invalid ID supplied');
        }
      } else {
        $response = array('success' => false, 'error' => 'No ID specified');
      }
    }
    else {
      $response = array('success' => false, 'error' => 'Invalid request type specified.');
    }
  } else {
    $response = array('success' => false, 'error' => 'No request specified.');
  }
  echo json_encode($response);


?>
