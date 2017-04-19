<?php
session_set_cookie_params(172800);
session_start();
require('core/config.php');
require('core/auth.php');
require('core/system.php');
require('core/phpmailer/PHPMailerAutoload.php');
$auth = new Auth;
$system = new System;
$mail = new PHPMailer;

$system->domain = $domain;
$system->db = $db;

$page['name'] = 'Encounters';

if(!$auth->isLogged()) {
	header('Location: index.php');
	exit;
}

$user = $system->getUserInfo($_SESSION['user_id']);
$system->setUserActive($user->id);

$settings = $system->getSettings();

if(isset($_GET['code'])) {
	$code = $_GET['code'];
	$code = $db->query("SELECT * FROM activation_codes WHERE code='".$code."' LIMIT 1");
	if($code->num_rows >= 1) {
		$code = $code->fetch_object();
		$db->query("UPDATE users SET is_verified='1' WHERE id='".$code->user_id."'");
        if($user->updated_preferences == 1) {
		header('Location: '.$system->getDomain().'/encounters');
		exit;
        } else {
        header('Location: '.$system->getDomain().'/wizard');
        exit;    
        }
	} else {
		header('Location: '.$system->getDomain().'/index.php');
		exit;
	}
} 

if(isset($_POST['resend'])) {
	// $mail->isSMTP();
    //$mail->Host = $settings->smtp_host;
	$mail->Host = 'localhost';
    $mail->SMTPAuth = true;
    $mail->Username = $settings->smtp_username;
    $mail->Password = $settings->smtp_password;
    $mail->SMTPSecure = $settings->smtp_encryption; 
    $mail->Port = $settings->smtp_port;
	
	// to pass GoDaddy restrictions
	$mail->SMTPDebug = 2;
	// $mail->isSMTP();
	// $mail->Host = 'localhost';
	// $mail->Port = 25;
	// $mail->SMTPAuth = false;
	// $mail->SMTPSecure = false;

    $mail->setFrom($settings->email_sender);
    //$mail->addAddress($user->email);
	$mail->addAddress('aswani15prakash@gmail.com');

    $mail->isHTML(true);

    $code = substr(md5(uniqid()),0,10);
    $mail->Subject = sprintf($lang['Verify_Account_Title'],$site_name);
    $content = '<a href="'.$system->getDomain().'/verify-email.php?code='.$code.'">this link</a>';
    $mail->Body = sprintf($lang['Verify_Account_Content'],$content);

    if(!$mail->send()) {
    	die('An error occurred, Mail could not be sent / '.$mail->ErrorInfo);
    }  

    $db->query("INSERT INTO activation_codes(user_id,code,time) VALUES (".$_SESSION['user_id'].",'".$code."',".time().")");
}

require('inc/top.php');
require('layout/verify-email.php');
require('inc/bottom.php');