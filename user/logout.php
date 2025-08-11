<?php
require_once '../config/config.php';

User::logout();

$_SESSION['logout_success'] = 'You have been successfully logged out.';
redirect('login.php');
?>