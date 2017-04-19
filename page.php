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

if(!$auth->isLogged()) {
	header('Location: index.php');
	exit;
}

$user = $system->getUserInfo($_SESSION['user_id']);
$system->setUserActive($user->id);

$settings = $system->getSettings();

$id = $db->real_escape_string($_GET['id']);

$_custom_page = $db->query("SELECT * FROM pages WHERE id='".$id."'");
if($_custom_page->num_rows >= 1) {
	$_custom_page = $_custom_page->fetch_object();
} else {
	header('Location: '.$system->getDomain().'/people');
	exit;
}

$menu['custom_page'][$_custom_page->id] = 'active';
$page['name'] = $_custom_page->page_title;

require('inc/top.php');
require('layout/page.php');
require('inc/bottom.php');