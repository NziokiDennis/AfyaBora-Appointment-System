<?php
session_set_cookie_params(["path" => "/Appointment_system/admin", "domain" => $_SERVER['HTTP_HOST'], "httponly" => true, "secure" => false]);
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
// recent 5 appointments for quick view
$recentApp = $conn->query("SELECT a.appointment_date, a.appointment_time, u.full_name AS patient, d.full_name AS doctor, a.status 
                          FROM appointments a
                          JOIN patients p ON a.patient_id = p.patient_id
                          JOIN users u ON p.user_id = u.user_id
                          JOIN users d ON a.doctor_id = d.user_id
                          ORDER BY a.appointment_date DESC, a.appointment_time DESC
                          LIMIT 5");
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
    .dashboard-header { background: #0d6efd; color: #fff; padding: 20px; border-radius: 8px; }
    .card { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    footer { margin-top: auto; }
  </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container">
  <div class="dashboard-header text-center mb-4">
      <h2>Welcome, <?php echo $admin_name; ?></h2>
      <p class="mb-0">Overview of system status and quick actions</p>
  </div>
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

  <!-- quick action cards -->
  <div class="row g-4 mt-4">
    <div class="col-md-6">
      <a href="users.php" class="text-decoration-none">
        <div class="card text-center p-4 bg-white shadow-sm h-100">
          <i class="fas fa-users-cog fa-3x text-primary"></i>
          <h5 class="mt-2">Manage Users</h5>
          <p class="text-muted">Create, edit or remove accounts</p>
        </div>
      </a>
    </div>
    <div class="col-md-6">
      <a href="reports/reports_dashboard.php" class="text-decoration-none">
        <div class="card text-center p-4 bg-white shadow-sm h-100">
          <i class="fas fa-file-alt fa-3x text-success"></i>
          <h5 class="mt-2">View Reports</h5>
          <p class="text-muted">See system analytics</p>
        </div>
      </a>
    </div>
  </div>

  <!-- recent appointments -->
  <div class="mt-5">
      <h4 class="mb-3">Recent Appointments</h4>
      <?php if ($recentApp && $recentApp->num_rows > 0): ?>
      <table class="table table-sm table-striped">
          <thead class="table-secondary">
              <tr><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr>
          </thead>
          <tbody>
              <?php while ($r = $recentApp->fetch_assoc()): ?>
                  <tr>
                      <td><?= $r['appointment_date'] ?></td>
                      <td><?= $r['appointment_time'] ?></td>
                      <td><?= htmlspecialchars($r['patient']) ?></td>
                      <td><?= htmlspecialchars($r['doctor']) ?></td>
                      <td><?= ucfirst($r['status']) ?></td>
                  </tr>
              <?php endwhile; ?>
          </tbody>
      </table>
      <?php else: ?>
      <p class="text-muted">No appointments yet.</p>
      <?php endif; ?>
  </div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
