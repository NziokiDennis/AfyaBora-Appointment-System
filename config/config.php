<?php
require_once __DIR__ . "/session.php";

app_start_session();

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
define("BASE_URL", $scheme . "://" . $host . app_url("/"));

// System-wide settings
define("SYSTEM_NAME", "AfyaBora Outpatients System");
define("SYSTEM_EMAIL", "support@afyaboraclinic.com");

?>
