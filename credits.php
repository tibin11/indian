<?php
session_set_cookie_params(172800);
session_start();
require('core/config.php');
require('core/auth.php');
require('core/system.php');
require('core/dom.php');
require('core/image.php');
$auth = new Auth;
$system = new System;

$system->domain = $domain;
$system->db = $db;

$menu['settings'] = 'active';
$page['name'] = 'Credits';

if(!$auth->isLogged()) {
	header('Location: index.php');
	exit;
}

$user = $system->getUserInfo($_SESSION['user_id']);
$system->setUserActive($user->id);

$settings = $system->getSettings();

if(isset($_POST['pay'])) {
	$amount = $_POST['amount'];
	$payment_gateway = $_POST['payment_gateway'];
	switch ($amount) {
		case 100:
		$price = $settings->credits_price_100;
		break;
		case 500:
		$price = $settings->credits_price_500;
		break;
		case 1000:
		$price = $settings->credits_price_1000;
		break;
		case 1500:
		$price = $settings->credits_price_1500;
		break;
		default:
		$price = 0;
		break;
	}
	if(isset($payment_gateway) && isset($_POST['amount'])) {
		if($payment_gateway == 1) {
			$query = array();
			$query['notify_url'] = $system->getDomain().'/api/paypal.php';
			$query['cmd'] = '_xclick';
			$query['business'] = $settings->paypal_email;
			$query['currency_code'] = $settings->currency;
			$query['custom'] = 'credits/'.$amount.'/'.$user->id;
			$query['return'] = $system->getDomain().'/credits';
			$query['item_name'] = $amount. 'Credits';
			$query['quantity'] = 1;
			$query['amount'] = $price;

			$query_string = http_build_query($query);
			header('Location: https://www.paypal.com/cgi-bin/webscr?' . $query_string);
		} 
	}
}

if(isset($_POST['add_spotlight'])) {
	$db->query("UPDATE users SET is_increased_exposure='1' WHERE id='".$user->id."'");
	$db->query("UPDATE users SET credits=credits-".$settings->feature_price." WHERE id='".$user->id."'");
	header('Location:'.$system->getDomain().'/credits');
	exit;
}

$page['js'] = '<script> var type; type=\'credits\'; </script>';
$page['js'] .= '<script src="https://assets.fortumo.com/fmp/fortumopay.js" type="text/javascript"></script>';

require('inc/top.php');
require('layout/credits.php');
require('inc/bottom.php');