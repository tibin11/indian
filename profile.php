<?php
session_set_cookie_params(172800);
session_start();
require('core/config.php');
require('core/auth.php');
require('core/geo.php');
require('core/system.php');
require('core/image.php');
$auth = new Auth;
$geo = new Geo;
$system = new System;

$system->domain = $domain;
$system->db = $db;

$menu['people'] = 'active';

if(!$auth->isLogged()) {
	header('Location: ../index.php');
	exit;
}

$id = $_GET['id'];

$user = $system->getUserInfo($_SESSION['user_id']);
$system->setUserActive($user->id);
$profile = $system->getUserInfo($id);

$settings = $system->getSettings();

if($user->credits >= $settings->gift_price) {
	$send_gift = '';
} else {
	$send_gift = 'disabled';
}

// Get dynamic profile content
$media = $db->query("SELECT * FROM uploaded_photos WHERE user_id='".$profile->id."' LIMIT 20");
$friends = $db->query("SELECT * FROM friend_requests WHERE accepted='1' AND (user1='".$id."' OR user2='".$id."') ORDER BY id DESC");
$gifts = $db->query("SELECT * FROM gifts WHERE user2='".$profile->id."'");

// Percentage Hot
$score = $system->getScore($profile->id);

// Check Friend Status
$check = $db->query("SELECT id,user1 FROM friend_requests WHERE ((user1='".$user->id."' AND user2='".$profile->id."') OR (user1='".$profile->id."' AND user2='".$user->id."')) AND accepted='0' LIMIT 1");
if($check->num_rows == 0) {
	$sent_request = 0;
} else {
	$sent_request = 1;
}
$check = $check->fetch_object();
if($check->user1 == $user->id) {
	$is_sender = 1;
} else {
	$is_sender = 0;
}
$check = $db->query("SELECT id FROM friend_requests WHERE ((user1='".$user->id."' AND user2='".$profile->id."') OR (user1='".$profile->id."' AND user2='".$user->id."')) AND accepted='1' LIMIT 1");
if($check->num_rows == 0) {
	$is_friend = 0;
} else {
	$is_friend = 1;
}

$photos = array();

// Fetch photos
if($media->num_rows == 0) { 
	$uploaded_photos = 0;
} else {
	$uploaded_photos = array();
	while($photo = $media->fetch_object()) {
		$photos[] = array('type'=>'uploaded','id' => $photo->id, 'path' => $photo->path);
	}
} 

// Photo Upload
if(isset($_POST['upload']) && !empty($_FILES)) {
	if($_FILES['photo_file']['name']) {
		$extension = strtolower(end(explode('.', $_FILES['photo_file']['name'])));
		if($extension == 'png' || $extension == 'jpg' || $extension == 'jpeg') {
			if(!$_FILES['photo_file']['error']) {
				$new_file_name = md5(mt_rand()).$_FILES['photo_file']['name'];
				if($_FILES['photo_file']['size'] > (1024000)) {
					$valid_file = false;
					$error = 'Oops! One of the photos you uploaded is too large';
				} else {
					$valid_file = true;
				}
				if($valid_file) {
					move_uploaded_file($_FILES['photo_file']['tmp_name'], 'uploads/'.$new_file_name);
					$resize = new ResizeImage('uploads/'.$new_file_name);
					$resize->resizeTo(640, 640);
					$resize->saveImage('uploads/'.$new_file_name);
					$uploaded = true;
					$db->query("INSERT INTO uploaded_photos (user_id,path,time) VALUES ('".$user->id."','".$new_file_name."','".time()."')");
					$db->query("UPDATE users SET uploaded_photos=uploaded_photos+1 WHERE id='".$user->id."'");
				}
			}
			else {
				$error = 'Error occured:  '.$_FILES['photo_file']['error'];
			}
		}	
	}
	header('Location: '.$system->getDomain().'/user/'.$user->id);
	exit;
}

// Record Profile View
if($user->is_incognito == 0 || $user->vip_expiration >= time()) {
	$check = $db->query("SELECT id FROM profile_views WHERE
		viewer_id='".$user->id."' AND profile_id='".$profile->id."' LIMIT 1");
	if($check->num_rows == 0) {
		$db->query("INSERT INTO profile_views (profile_id,viewer_id,time) VALUES ('".$profile->id."','".$user->id."','".time()."')");
	}
}

// Sexual Orientation
switch ($profile->sexual_interest) {
	case 1:
	$sexual_orientation = 'Straight';
	break;
	case 2: 
	$sexual_orientation = 'Gay';
	break;
	case 3:
	$sexual_orientation = 'Lesbian';
	break;
	case 4: 
	$sexual_orientation = 'Bisexual';
	break;
	default:
	$sexual_orientation = 'Straight';
	break;
}

// Calculate distance
if($user->country == $profile->country) {
	$distance = $geo->coordsToKm($user->latitude,$user->longitude,$profile->latitude,$profile->longitude);
} 

// Get profile page ad
$ads = $db->query("SELECT * FROM ads LIMIT 1");
$ad = $ads->fetch_object();

$page['name'] = $profile->full_name;

require('inc/top.php');
require('layout/profile.php');
require('inc/bottom.php');