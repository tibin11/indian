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
$page['name'] = 'VIP Account';

if(!$auth->isLogged()) {
	header('Location: index.php');
	exit;
}

$user = $system->getUserInfo($_SESSION['user_id']);
$system->setUserActive($user->id);

$settings = $system->getSettings();

if(isset($_POST['pay'])) {
	$vip_duration = $_POST['vip_duration'];
	$payment_gateway = $_POST['payment_gateway'];
	if(isset($payment_gateway) && isset($_POST['vip_duration'])) {

		if($payment_gateway == 1) {
			if($vip_duration == 1) {
				$query = array();
				$query['notify_url'] = $system->getDomain().'/api/paypal.php';
				$query['cmd'] = '_xclick';
				$query['business'] = $settings->paypal_email;
				$query['currency_code'] = $settings->currency;
				$query['custom'] = 'vip/2629743/'.$user->id;
				$query['return'] = $system->getDomain().'/vip';
				$query['item_name'] = 'VIP Account - 1 Month';
				$query['quantity'] = 1;
				$query['amount'] = $settings->vip_1_month;

				$query_string = http_build_query($query);
				header('Location: https://www.paypal.com/cgi-bin/webscr?' . $query_string);
			}
			if($vip_duration == 2) {
				$query = array();
				$query['notify_url'] = $system->getDomain().'/api/paypal.php';
				$query['cmd'] = '_xclick';
				$query['business'] = $settings->paypal_email;
				$query['currency_code'] = $settings->currency;
				$query['custom'] = 'vip/7889229/'.$user->id;
				$query['return'] = $system->getDomain().'/vip';
				$query['item_name'] = 'VIP Account - 3 Months';
				$query['quantity'] = 1;
				$query['amount'] = $settings->vip_3_months;

				$query_string = http_build_query($query);
				header('Location: https://www.paypal.com/cgi-bin/webscr?' . $query_string);
			}
			if($vip_duration == 3) {
				$query = array();
				$query['notify_url'] = $system->getDomain().'/api/paypal.php';
				$query['cmd'] = '_xclick';
				$query['business'] = $settings->paypal_email;
				$query['currency_code'] = $settings->currency;
				$query['custom'] = 'vip/15778458/'.$user->id;
				$query['return'] = $system->getDomain().'/vip';
				$query['item_name'] = 'VIP Account - 6 Months';
				$query['quantity'] = 1;
				$query['amount'] = $settings->vip_6_months;

				$query_string = http_build_query($query);
				header('Location: https://www.paypal.com/cgi-bin/webscr?' . $query_string);
			}
		} 
	}
}

$page['js'] = '<script> var type; type=\'vip\'; </script>';
$page['js'] .= '<script src="https://assets.fortumo.com/fmp/fortumopay.js" type="text/javascript"></script>';

require('inc/top.php');
require('layout/vip.php');
require('inc/bottom.php');