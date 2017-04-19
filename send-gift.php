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

$user = $system->getUserInfo($_SESSION['user_id']);

$settings = $system->getSettings();

$profile_id = $_POST['profile_id'];

if(isset($_POST['send_gift'])) { 
	$gift = $_POST['giftValue'];
	if(!empty($gift) && $user->credits >= $settings->gift_price) {
		$gift_path = $gift.'.png';
		$db->query("INSERT INTO gifts (user1,user2,path,time) VALUES ('".$user->id."','".$profile_id."','".$gift_path."','".time()."')");
		$db->query("UPDATE users SET credits=credits-".$settings->gift_price." WHERE id='".$user->id."'");
		header('Location: '.$system->getDomain().'/user/'.$profile_id);
		exit;
	} else {
		header('Location: '.$system->getDomain().'/credits');
		exit;
	}
}