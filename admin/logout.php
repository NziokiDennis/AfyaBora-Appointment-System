<?php
// destroy session and cookie properly
session_set_cookie_params(["path" => "/Appointment_system/admin", "domain" => $_SERVER['HTTP_HOST'], "httponly" => true]);
session_start();
// clear session array
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
header("Location: login.php");
exit;
?>
