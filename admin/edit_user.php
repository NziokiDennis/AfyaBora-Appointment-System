<?php
require_once "admin_auth.php";
require_once "../config/db.php";


$user_id = $_GET["id"];
$success = $error = "";

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $full_name = $_POST["full_name"];
  $email = $_POST["email"];
  $phone = $_POST["phone_number"];
  $role = $_POST["role"];

  $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ?, role = ? WHERE user_id = ?");
  $stmt->bind_param("ssssi", $full_name, $email, $phone, $role, $user_id);
  if ($stmt->execute()) {
    $success = "User updated!";
  } else {
    $error = "Update failed: " . $stmt->error;
  }
}
?>

<!DOCTYPE html>
<html>
<head><title>Edit User</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container mt-5">
  <h2>Edit User</h2>
  <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <form method="POST">
    <div class="mb-3"><input type="text" name="full_name" class="form-control" value="<?= $user['full_name'] ?>" required></div>
    <div class="mb-3"><input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" required></div>
    <div class="mb-3"><input type="text" name="phone_number" class="form-control" value="<?= $user['phone_number'] ?>"></div>
    <div class="mb-3">
      <select name="role" class="form-select">
        <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
        <option value="doctor" <?= $user['role']=='doctor'?'selected':'' ?>>Doctor</option>
        <option value="patient" <?= $user['role']=='patient'?'selected':'' ?>>Patient</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Update User</button>
  </form>
</div>

<?php include "../partials/footer.php"; ?>
</body>
</html>
