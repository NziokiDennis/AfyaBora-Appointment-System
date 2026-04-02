<?php
require_once "../config/session.php";

app_start_session();

if (isset($_SESSION["admin_id"])) {
    app_redirect("/admin/dashboard.php");
}

app_redirect("/admin/login.php");
