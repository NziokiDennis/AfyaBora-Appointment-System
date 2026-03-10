<?php
// landing page for admin folder; redirect to login or dashboard based on session
session_set_cookie_params(["path" => "/Appointment_system/admin", "httponly" => true]);
session_start();
if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
