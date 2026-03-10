<?php
require_once "admin_auth.php";
require_once "../config/db.php";


$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $full_name = trim($_POST["full_name"]);
  $email = trim($_POST["email"]);
  $phone = trim($_POST["phone_number"]);
  $role = $_POST["role"];
  $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

  $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone_number, role) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("sssss", $full_name, $email, $password, $phone, $role);
  if ($stmt->execute()) {
    $success = "User added successfully!";
  } else {
    $error = "Failed to add user: " . $stmt->error;
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add User</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container mt-5">
  <h2>Add New User</h2>
  <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
  
  <form method="POST">
    <div class="mb-3"><input type="text" name="full_name" class="form-control" placeholder="Full Name" required></div>
    <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
    <div class="mb-3"><input type="text" name="phone_number" class="form-control" placeholder="Phone"></div>
    <div class="mb-3">
      <select name="role" class="form-select" required>
        <option disabled selected>Select Role</option>
        <option value="admin">Admin</option>
        <option value="doctor">Doctor</option>
        <option value="patient">Patient</option>
      </select>
    </div>
    <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
    <button type="submit" class="btn btn-primary">Create User</button>
  </form>
</div>

<?php include "../partials/footer.php"; ?>
</body>
</html>
