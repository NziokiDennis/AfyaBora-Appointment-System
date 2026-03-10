<?php
// make sure session cookie is scoped to the admin directory for cross-machine consistency
session_set_cookie_params(["path" => "/Appointment_system/admin", "domain" => $_SERVER['HTTP_HOST'], "httponly" => true, "secure" => false]);
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}
?>
