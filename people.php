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

$menu['people'] = 'active';
$page['name'] = 'People';

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
	$filter->order_by = 4;
	$filter->age_range = $minimum_age.',100';
	$filter->distance_range = '0,500';
	$filter->location_dating = 0;
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
		header('Location: '.$system->getDomain().'/people');
		exit;
	} else {
		$db->query("UPDATE filters SET sexual_preference='".$sexual_preference."',country='".$country."',order_by='".$order_by."',sexual_orientation='".$sexual_orientation."',age_range='".$age_range."',distance_range='".$distance_range."',location_dating='".$location_dating."' WHERE user_id='".$user->id."'");
		header('Location: '.$system->getDomain().'/people');
		exit;
	}
}

// Format Query
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

$_age_range = explode(',',$filter->age_range);
$age_range = 'AND age >= '.$_age_range[0].' AND age <= '.$_age_range[1].'';

$distance_range = explode(',',$filter->distance_range);

switch ($filter->order_by) {
	case 1:
	$order = 'ORDER BY id DESC';
	break;
	case 2:
	$order = 'ORDER BY id ASC';
	break;
	case 3:
	$order = 'ORDER BY last_active DESC';
	break;
	case 4:
	$order = 'ORDER BY RAND()';
	break;
	default:
	$order = 'ORDER BY RAND()';
	break;
}


	if($filter->location_dating == 1) {
	$people = "
	SELECT *,
    3956 * 2 * ASIN(SQRT( POWER(SIN((".$user->latitude." - latitude) * pi()/180 / 2), 2) + COS(".$user->latitude." * pi()/180) * COS(latitude * pi()/180) *
    POWER(SIN((".$user->longitude." - longitude) * pi()/180 / 2), 2) )) as
    distance FROM users
    HAVING distance >= ".$distance_range[0]." AND distance <= ".$distance_range[1]." AND id != ".$user->id." $gender $sexual_orientation $country $age_range AND profile_picture!='default_avatar.png' $order
    ";
    } else {
    $people = "
	SELECT * FROM users
    WHERE id != ".$user->id." $gender $sexual_orientation $country $age_range AND profile_picture!='default_avatar.png' $order
    ";
    }



// Pagination
$per_page = 12;
$count = $db->query($people)->num_rows;
$last_page = ceil($count/$per_page);
if(isset($_GET['p'])) { $p = $_GET['p']; } else { $p = 1; }
if($p < 1) { $p = 1; } elseif($p > $last_page) { $p = $last_page; }
$limit = 'LIMIT ' .($p - 1) * $per_page .',' .$per_page;
$people .= " $limit";


// Finalize Query
$people = $db->query($people);

require('inc/top.php');
require('layout/people.php');
require('inc/bottom.php');
