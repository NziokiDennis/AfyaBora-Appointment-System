<?php
require_once __DIR__ . "/session.php";

app_start_session();

if (!isset($_SESSION["user_id"])) {
    app_redirect("/login.php");
}

function checkRole($required_role) {
    if (($_SESSION["role"] ?? null) !== $required_role) {
        app_redirect("/index.php");
    }
}
?>
