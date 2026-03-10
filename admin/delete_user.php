<?php
require_once "admin_auth.php";
require_once '../config/db.php';


if (isset($_GET["id"])) {
  $id = $_GET["id"];
  $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
}

header("Location: users.php");
exit;
