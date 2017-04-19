<?php
session_set_cookie_params(172800);
session_start();
require('core/config.php');
require('core/auth.php');
require('core/geo.php');
require('core/system.php');
require('core/fb-sdk/src/facebook.php');
$auth = new Auth;
$system = new System;
$geo = new Geo;

$system->domain = $domain;
$system->db = $db;

$ip = $_SERVER['REMOTE_ADDR'];

$app_id = $fb_app_id;
$app_secret = $fb_secret_key;

$facebook = new Facebook(array(
  'appId'  => $app_id,
  'secret' => $app_secret,
  ));

// Getting User ID
$user = $facebook->getUser();

// Get Access token
$access_token = $facebook->getAccessToken();

if ($user) {
  try {

    //$user_friendList = $facebook->api('/me/friends?access_token='.$access_token);
    $user_profile = $facebook->api('/me?fields=id,name,email,gender,birthday','GET');
    if(!empty($user_profile['email'])) {
      $email = $user_profile['email'];
    } else {
      $email = 'missing';
    }
    $full_name = $user_profile['name'];
    $id = $user_profile['id'];
    if(!empty($user_profile['birthday'])) {
      $birthday = $user_profile['birthday'];
    } else {
      $birthday = 'missing';  
    }
    if(!empty($user_profile['gender'])) {
      $gender = ucfirst($user_profile['gender']);
    } else {
      $gender = 'missing';  
    }
    $time = time();
    $profile_picture = 'http://graph.facebook.com/'.$id.'/picture?type=large';

    // Geolocation
    $longitude = $_SESSION['longitude'];
    $latitude = $_SESSION['latitude'];

    $geo_info = $geo->getInfo($latitude,$longitude);
    $city = $geo_info['geonames'][0]['name'];
    $country = $geo_info['geonames'][0]['countryName'];

    $check = $db->query("SELECT * FROM users WHERE email='$email'");
    $check = $check->num_rows;

    if($check >= 1) {

     // Account exists
     $db->query("UPDATE users SET last_login='UNIX_TIMESTAMP()',ip='".$ip."',longitude='".$longitude."',latitude='".$latitude."' WHERE email='$email'");

     $_SESSION['auth'] = true;
     $_SESSION['email'] = $email;
     $_SESSION['full_name'] = $full_name;

   } else {

     // Create account
     $db->query("INSERT INTO users (profile_picture,full_name,email,registered,credits,ip,age,gender,last_login,country,city,longitude,latitude) VALUES ('$profile_picture','$full_name','$email','$time','10','$ip','$age','$gender','$time','".$country."','".$city."','".$longitude."','".$latitude."')");

     $_SESSION['auth'] = true;
     $_SESSION['email'] = $email;
     $_SESSION['full_name'] = $full_name;

   }
   
 } catch (FacebookApiException $e) {
  error_log($e);
  $user = null;
}

} 

if ($user) {
  
  $user_profile = $facebook->api('/me?fields=id,name,email,gender','GET');
  $email = $user_profile['email'];

  $myuser = $db->query("SELECT * FROM users WHERE email='$email'");
  $myuser = $myuser->fetch_array();

  $db->query("UPDATE users SET last_login='".time()."',ip='$ip' WHERE email='$email'");

  $_SESSION['auth'] = true;
  $_SESSION['email'] = $myuser['email'];
  $_SESSION['user_id'] = $myuser['id'];
  $_SESSION['full_name'] = $myuser['full_name'];

  header('Location: '.$system->getDomain().'/people');

} else {
  $loginUrl = $facebook->getLoginUrl(array('scope' => 'email,user_birthday,user_about_me,user_location'));
  header('Location: '.$loginUrl);
}
