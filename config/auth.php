<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Ensure correct role access
function checkRole($required_role) {
    if ($_SESSION["role"] !== $required_role) {
        header("Location: ../index.php"); // Redirect to home if unauthorized
        exit;
    }
}
?>
