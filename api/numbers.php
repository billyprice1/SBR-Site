<?php
  include "debug.php";
  require "mysql.php";
  $mysql_connection = mysqli_connect($mysql_ip, $mysql_user, $mysql_pass, $mysql_db);
  header("Content-Type: application/json");
  $response = array();
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
      if(isset($_GET['data'])) {
        $data = $_GET['data'];
        $q = mysqli_query($mysql_connection, "INSERT INTO `scam_numbers` (phone_number, submitted_ip, submitted_name)
        VALUES ('".mysqli_real_escape_string($mysql_connection, $data)."', '".mysqli_real_escape_string($mysql_connection, $ip)."', '".mysqli_real_escape_string($mysql_connection, $specifiedname)."')");
        if(!$q) {
          $response = array('success' => false, 'error' => 'SQL Error; Summary: '.mysqli_error($mysql_connection));
        } else {
          $response = array('success' => true, 'summary' => array('data' => $data, 'ip' => $ip, 'name' => $specifiedname));
        }
      } else {
        $response = array('success' => false, 'error' => 'No data was given');
      }
    } else if($req == "get") {
      $limit = 10;
      if(isset($_GET['limit'])) {
        $limit = intval($_GET['limit']);
      }
      $q = mysqli_query($mysql_connection, "SELECT id,phone_number,submitted_name,submitted_date FROM `scam_numbers` WHERE approved=1 ORDER BY id DESC LIMIT ".$limit);
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
