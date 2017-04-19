<?php
session_set_cookie_params(172800);
session_start();
require('core/config.php');
require('core/auth.php');
require('core/system.php');
$auth = new Auth;
$system = new System;

$system->domain = $domain;
$system->db = $db;

$menu['encounters'] = 'active';
$page['name'] = 'Encounters';

if(!$auth->isLogged()) {
	header('Location: index.php');
	exit;
}

$user = $system->getUserInfo($_SESSION['user_id']);
$system->setUserActive($user->id);

$settings = $system->getSettings();

// Filter
$filter = $db->query("SELECT * FROM filters WHERE user_id='".$user->id."'");
$num_rows = $filter->num_rows;
if($num_rows >= 1) {
	$filter = $filter->fetch_object();
} else {
	$filter = new stdClass();
	$filter->sexual_preference = 3;
	$filter->sexual_orientation = 1;
	$filter->country = 'All Countries';
	$filter->city = '';
	$filter->order_by = 4;
}

// Create / Update Filter
if(isset($_POST['filter'])) {
	$sexual_preference = $_POST['sexual_preference'];
	$sexual_orientation = $_POST['sexual_orientation'];
	$country = $_POST['country'];
	$order_by = $_POST['order_by'];
	$age_range = $_POST['age_range'];
	$distance_range = $_POST['distance_range'];
	if(isset($_POST['location_dating'])) {
		$location_dating = 1;
	} else {
		$location_dating = 0;
	}

	if($num_rows == 0) {
		$db->query("INSERT INTO filters (user_id,sexual_preference,country,order_by,sexual_orientation,age_range,distance_range,location_dating) VALUES ('".$user->id."','".$sexual_preference."','".$country."','".$order_by."','".$sexual_orientation."','".$age_range."','".$distance_range."','".$location_dating."')");
		header('Location: '.$system->getDomain().'/encounters');
		exit;
	} else {
		$db->query("UPDATE filters SET sexual_preference='".$sexual_preference."',country='".$country."',order_by='".$order_by."',sexual_orientation='".$sexual_orientation."',age_range='".$age_range."',distance_range='".$distance_range."',location_dating='".$location_dating."' WHERE user_id='".$user->id."'");
		header('Location: '.$system->getDomain().'/encounters');
		exit;	
	}
}

// Format Query 
$show = 'WHERE NOT EXISTS (SELECT id FROM profile_likes WHERE profile_id=users.id AND viewer_id='.$user->id.') AND NOT EXISTS (SELECT id FROM profile_dislikes WHERE profile_id=users.id AND viewer_id='.$user->id.')';

switch ($filter->sexual_preference) {
	case 1:
	$gender = 'AND gender=\'Male\'';
	break;
	case 2:
	$gender = 'AND gender=\'Female\'';
	break;
	case 3:
	$gender = 'AND gender IS NOT NULL';
	break;
	default:
	$gender = 'AND gender IS NOT NULL';
	break;
}

switch ($filter->sexual_orientation) {
	case 1:
	$sexual_orientation = 'AND sexual_interest=\'1\'';
	break;
	case 2:
	$sexual_orientation = 'AND sexual_interest=\'2\'';
	break;
	case 3:
	$sexual_orientation = 'AND sexual_interest=\'3\'';
	break;
	case 4:
	$sexual_orientation = 'AND sexual_interest=\'4\'';
	break;
	default:
	$sexual_orientation = 'AND country LIKE \'%'.$filter->sexual_orientation.'%\'';
	break;
}

switch ($filter->country) {
	case 'All Countries':
	$country = 'AND country IS NOT NULL';
	break;
	default:
	$country = 'AND country LIKE \'%'.$filter->country.'%\'';
	break;
}

$city = 'AND city LIKE \'%'.$filter->city.'%\'';

$order = 'ORDER BY RAND()';

$_age_range = explode(',',$filter->age_range);
$distance_range = explode(',',$filter->distance_range);

// Count users left
$count =  $db->query("SELECT COUNT(id) FROM users $show $gender $sexual_orientation $country $me $city AND id != ".$user->id." AND profile_picture!='default_avatar.png' $order LIMIT 1");
$count = $count->fetch_row();
$count = $count[0];

if(empty($user->last_encounter) && $count >= 1) {
$people = "SELECT * FROM users $show $gender $sexual_orientation $country $me $city AND id != ".$user->id." AND profile_picture!='default_avatar.png' $order LIMIT 5";
} else {
if($count >= 1) {
$people = "SELECT * FROM users WHERE id='".$user->last_encounter."'";
} else {
$no_encounters = true;
}
}

// Finalize Query
$people = $db->query($people);

require('inc/top.php');
require('layout/encounters.php');
require('inc/bottom.php');