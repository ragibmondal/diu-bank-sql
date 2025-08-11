<?php
require_once '../config/config.php';

Admin::logout();

$_SESSION['logout_success'] = 'You have been successfully logged out.';
redirect('admin/login.php');
?>