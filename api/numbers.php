<?php
  function isPhoneNum($num) {
    return preg_match("/^\+[0-9]{3} \([2-9]{1}[0-9]{2}\) [2-9]{1}[0-9]{2}-[0-9]{4}$/", $num);
  }

  function splitNumber($num) {
    $splitted = explode(" ",$num);
    $country = substr($splitted[0], 1);
    $area = substr($splitted[1],1,3);
    $s = explode("-", $splitted[2]);
    $office = $s[0];
    $subscriber = $s[1];
    return array($country, $area, $office, $subscriber);
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
  if($req_count > 7) {
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
        $q = mysqli_query($mysql_connection, "INSERT INTO `scam_numbers` (country, area, office, subscriber, submitted_ip, submitted_name)
        VALUES ('".$splitted[0]."', '".$splitted[1]."', '".$splitted[2]."', '".$splitted[3]."', '".mysqli_real_escape_string($mysql_connection, $ip)."', '".mysqli_real_escape_string($mysql_connection, $specifiedname)."')");
        if(!$q) {
          $response = array('success' => false, 'error' => 'SQL Error; Summary: '.mysqli_error($mysql_connection));
        } else {
          $response = array('success' => true, 'summary' => array('country' => $splitted[0], 'area' => $splitted[1], 'office' => $splitted[2], 'subscriber' => $splitted[3], 'ip' => $ip, 'name' => $specifiedname));
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
      $q = mysqli_query($mysql_connection, "SELECT id,country,area,office,subscriber,submitted_name,submitted_date FROM `scam_numbers` WHERE approved=1 ORDER BY id DESC LIMIT ".$limit);
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
    } else {
      $response = array('success' => false, 'error' => 'Invalid request type specified.');
    }
  } else {
    $response = array('success' => false, 'error' => 'No request specified.');
  }
  echo json_encode($response);


?>
