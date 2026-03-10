<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}
$admin_name = $_SESSION["full_name"];
require_once "../config/db.php";

// Quick stats
$users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()["total"];
$patients = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'patient'")->fetch_assoc()["total"];
$doctors = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'doctor'")->fetch_assoc()["total"];
$appointments = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f4f4f4; min-height: 100vh; display: flex; flex-direction: column; }
    .container { margin-top: 60px; }
    .card { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    footer { margin-top: auto; }
  </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container">
  <h2 class="text-center mb-4 text-primary">Welcome, <?php echo $admin_name; ?></h2>
  <div class="row g-4">
    <div class="col-md-3">
      <div class="card text-center bg-light">
        <div class="card-body">
          <i class="fas fa-users fa-2x text-primary"></i>
          <h5 class="card-title mt-2">All Users</h5>
          <p class="card-text"><?php echo $users; ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center bg-light">
        <div class="card-body">
          <i class="fas fa-user-md fa-2x text-success"></i>
          <h5 class="card-title mt-2">Doctors</h5>
          <p class="card-text"><?php echo $doctors; ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center bg-light">
        <div class="card-body">
          <i class="fas fa-user-injured fa-2x text-warning"></i>
          <h5 class="card-title mt-2">Patients</h5>
          <p class="card-text"><?php echo $patients; ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center bg-light">
        <div class="card-body">
          <i class="fas fa-calendar-alt fa-2x text-danger"></i>
          <h5 class="card-title mt-2">Appointments</h5>
          <p class="card-text"><?php echo $appointments; ?></p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
