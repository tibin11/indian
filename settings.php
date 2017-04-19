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
$page['name'] = 'Settings';

if(!$auth->isLogged()) {
	header('Location: index.php');
	exit;
}

$user = $system->getUserInfo($_SESSION['user_id']);
$system->setUserActive($user->id);

$settings = $system->getSettings();

$payments = $db->query("SELECT * FROM transactions WHERE user_id='".$user->id."' AND status='1' LIMIT 5");

if(isset($_POST['save_1'])) {

	$full_name = $system->secureField($_POST['full_name']);
	$gender = $system->secureField($_POST['gender']);
	$city = $system->secureField($_POST['city']);
	$explode = explode(',', $city);
	$country = $explode[2];
	$city = $explode[0];
	$age = $system->secureField($_POST['age']);
	$height = $system->secureField($db->real_escape_string($_POST['height']));
	$weight = $system->secureField($_POST['weight']);
	$bio = $system->secureField($db->real_escape_string($_POST['bio']));
	$sexual_orientation = $system->secureField($_POST['sexual_orientation']);
	$interests = $system->secureField($_POST['interests']);

	$db->query("
		UPDATE users SET 
		full_name='".$full_name."',
		gender='".$gender."',
		country='".$country."',
		city='".$city."',
		age='".$age."',
		bio='".$bio."',
		sexual_interest='".$sexual_orientation."',
		interests='".$interests."',
		updated_preferences='1',
		height='".$height."',
		weight='".$weight."'
		WHERE id='".$user->id."'");

	if(!empty($_POST['new_password']) && !empty($_POST['confirm_new_password'])) { 
		
		if($new_password === $confirm_new_password) {
			$new_password = $auth->hashPassword($new_password);

			$db->query("
				UPDATE users SET 
				password='".$new_password."'
				WHERE id='".$user->id."'");
		}

	}

	$_SESSION['language'] = $website_language;
	
	header('Location: settings.php');
	exit;

}

if(isset($_POST['save_2'])) {

	$email = $system->secureField($_POST['email']);
	$new_password = $system->secureField($_POST['new_password']);
	$confirm_new_password = $system->secureField($_POST['confirm_new_password']);
	$website_language = $system->secureField($_POST['website_language']);
	$instagram_username = $system->secureField($_POST['instagram_username']);

	$db->query("
		UPDATE users SET 
		email='".$email."',
		language='".$website_language."',
		instagram_username='".$instagram_username."',
		updated_preferences='1'
		WHERE id='".$user->id."'");

	if(!empty($_POST['new_password']) && !empty($_POST['confirm_new_password'])) { 
		
		if($new_password === $confirm_new_password) {
			$new_password = $auth->hashPassword($new_password);

			$db->query("
				UPDATE users SET 
				password='".$new_password."'
				WHERE id='".$user->id."'");
		}

	}

	$_SESSION['language'] = $website_language;

	if(!empty($_FILES)) {
		if($_FILES['profile_photo']['name']) {
			$extension = strtolower(end(explode('.', $_FILES['profile_photo']['name'])));
			if($extension == 'png' || $extension == 'jpg' || $extension == 'jpeg') {
				if(!$_FILES['profile_photo']['error']) {
					$new_file_name = md5(mt_rand()).$_FILES['profile_photo']['name'];
					if($_FILES['profile_photo']['size'] > (1024000)) {
						$valid_file = false;
						$error = 'Oops! One of the photos you uploaded is too large';
					} else {
						$valid_file = true;
					}
					if($valid_file) {
						move_uploaded_file($_FILES['profile_photo']['tmp_name'], 'uploads/'.$new_file_name);
						$resize = new ResizeImage('uploads/'.$new_file_name);
						$resize->resizeTo(640, 640);
						$resize->saveImage('uploads/'.$new_file_name);
						$uploaded = true;
						$db->query("UPDATE users SET profile_picture='".$new_file_name."' WHERE id='".$user->id."'");
					}
				}
				else {
					$error = 'Error occured:  '.$_FILES['profile_photo']['error'];
				}
			}	
		}
	}

	header('Location: settings.php');
	exit;

}

if(isset($_GET['sync'])) {
	if(!empty($user->instagram_username)) {

		$images = array();
		$html = file_get_html('http://instaliga.com/'.$user->instagram_username);
		if($html) {
			foreach($html->find('a[class=element__image-wrapper]') as $element) {
				$elm = $element->innertext;
				preg_match('!https?://[\w+&@#/%?=~|\!\:,.;-]*[\w+&@#/%=~|-]!', $elm, $url);
				$url[0] = str_replace('/s320x320/', '/', $url[0]);
				$url = trim($url[0]);
				$save_path = md5(mt_rand());
				$ch = curl_init($url);
				$fp = fopen('uploads/'.$save_path, 'wb');
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_exec($ch);
				curl_close($ch);
				fclose($fp);
				$resize = new ResizeImage('uploads/'.$save_path);
				$resize->resizeTo(640, 640);
				$resize->saveImage('uploads/'.$save_path);
				$db->query("INSERT INTO uploaded_photos (user_id,path,is_instagram,time) VALUES ('".$user->id."','".$save_path."','1','".time()."')");
				$db->query("UPDATE users SET uploaded_photos=uploaded_photos+1 WHERE id='".$user->id."'");
			}
		}

	}
}


require('inc/top.php');
require('layout/settings.php');
require('inc/bottom.php');