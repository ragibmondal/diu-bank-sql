<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('SITE_URL', 'https://droqai.tech/');

define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('CLASS_PATH', ROOT_PATH . 'classes/');
define('ADMIN_PATH', ROOT_PATH . 'admin/');
define('USER_PATH', ROOT_PATH . 'user/');

require_once CONFIG_PATH . 'database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Dhaka');

function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function check_user_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function check_admin_login() {
    if (!is_admin_logged_in()) {
        redirect('admin/login.php');
    }
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $file = CLASS_PATH . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>