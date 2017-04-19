<?php
session_set_cookie_params(172800);
session_start();
require('core/auth.php');
$auth = new Auth;

$auth->logOut();

