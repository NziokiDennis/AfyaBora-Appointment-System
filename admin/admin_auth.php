<?php
require_once __DIR__ . "/../config/session.php";

app_start_session();

if (!isset($_SESSION["admin_id"])) {
    app_redirect("/admin/login.php");
}
?>
