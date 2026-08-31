<?php
require_once "admin_auth.php";
require_once "../config/db.php";

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'users';
$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $full_name = trim($_POST["full_name"]);
  $email = trim($_POST["email"]);
  $phone = trim($_POST["phone_number"]);
  $role = $_POST["role"];
  $specialization = ($role === "doctor") ? trim($_POST["specialization"] ?? "") : null;
  if ($specialization === "") { $specialization = null; }
  $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

  $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone_number, role, specialization) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssss", $full_name, $email, $password, $phone, $role, $specialization);
  if ($stmt->execute()) {
    $success = "User added successfully!";
  } else {
    $error = "Failed to add user: " . $stmt->error;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add User — HealthAdmin</title>
</head>
<body>
<?php include "sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Add User</div>
      <div class="topbar-crumb">
        <a href="dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="users.php">Users</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        Add
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
    </div>
  </header>

  <main class="page-content">
    <div class="page-header">
      <h2><i class="fas fa-user-plus"></i> Add New User</h2>
      <p>Create doctor, patient, receptionist, or admin accounts from one place.</p>
    </div>

    <?php if ($success): ?>
      <div class="ha-alert ha-alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="ha-alert ha-alert-danger"><i class="fas fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="ha-card" style="max-width:560px">
      <form method="POST">
        <div class="ha-form-group">
          <label class="ha-label">Full Name</label>
          <input type="text" name="full_name" class="ha-input" placeholder="Full Name" required>
        </div>
        <div class="ha-form-group">
          <label class="ha-label">Email</label>
          <input type="email" name="email" class="ha-input" placeholder="Email" required>
        </div>
        <div class="ha-form-group">
          <label class="ha-label">Phone Number</label>
          <input type="text" name="phone_number" class="ha-input" placeholder="Phone">
        </div>
        <div class="ha-form-group">
          <label class="ha-label">Role</label>
          <select name="role" id="roleSelect" class="ha-select" required onchange="toggleSpecialization()">
            <option disabled selected>Select Role</option>
            <option value="admin">Admin</option>
            <option value="doctor">Doctor</option>
            <option value="receptionist">Receptionist</option>
            <option value="patient">Patient</option>
          </select>
        </div>
        <div class="ha-form-group" id="specializationGroup" style="display:none">
          <label class="ha-label">Specialization</label>
          <input type="text" name="specialization" class="ha-input" placeholder="e.g. Pediatrics, Cardiology, General Practice">
        </div>
        <div class="ha-form-group">
          <label class="ha-label">Password</label>
          <input type="password" name="password" class="ha-input" placeholder="Password" required>
        </div>
        <script>
          function toggleSpecialization() {
            const role = document.getElementById('roleSelect').value;
            document.getElementById('specializationGroup').style.display = (role === 'doctor') ? 'block' : 'none';
          }
        </script>
        <div style="display:flex;gap:10px;margin-top:8px">
          <button type="submit" class="ha-btn ha-btn-primary"><i class="fas fa-save"></i> Create User</button>
          <a href="users.php" class="ha-btn ha-btn-ghost"><i class="fas fa-arrow-left"></i> Back to Users</a>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>
