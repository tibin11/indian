<?php
session_set_cookie_params(172800);
session_start();
require('core/config.php');
require('core/auth.php');
require('core/dom.php');
require('core/instagram.php');
require('core/system.php');
$auth = new Auth;
$system = new System;
$instagram = new Instagram;

$system->domain = $domain;
$system->db = $db;

$menu['encounters'] = 'active';
$page['name'] = 'Welcome';

if(!$auth->isLogged()) {
	header('Location: index.php');
	exit;
}

$user = $system->getUserInfo($_SESSION['user_id']);
$system->setUserActive($user->id);

$settings = $system->getSettings();

if($user->updated_preferences == 1) {
	header('Location: '.$system->getDomain().'/encounters');
	exit;
}

if(isset($_POST['save'])) {

	$city = $system->secureField($_POST['city']);
	$explode = explode(',', $city);
	$country = $explode[2];
	$city = $explode[0];
	$age = $system->secureField($_POST['age']);
	$gender = $system->secureField($_POST['gender']);
	$sexual_orientation = $system->secureField($_POST['sexual_orientation']);
	$height = $_POST['height'];
	$weight = $_POST['weight'];

	$db->query("UPDATE users SET 
		country='".$country."',
		city='".$city."',
		age='".$age."',
		gender='".$gender."',
		sexual_interest='".$sexual_orientation."',
		height='".$height."',
		weight='".$weight."',
		updated_preferences='1' WHERE id='".$user->id."'");

	header('Location: '.$system->getDomain().'/encounters');
	exit;

}

require('inc/top.php');
require('layout/wizard.php');
require('inc/bottom.php');