<?php
require_once "admin_auth.php";
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'users';

$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) { header("Location: users.php"); exit; }

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) { header("Location: users.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone_number'] ?? '');
    $role      = $_POST['role'] ?? 'patient';

    if (!$full_name || !$email) {
        $error = "Name and email are required.";
    } else {
        $upd = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, role=? WHERE user_id=?");
        $upd->bind_param("ssssi", $full_name, $email, $phone, $role, $user_id);
        if ($upd->execute()) {
            header("Location: users.php?success=updated");
            exit;
        } else {
            $error = "Update failed: " . $upd->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User — HealthAdmin</title>
</head>
<body>
<?php include "sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Edit User</div>
      <div class="topbar-crumb">
        <a href="dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="users.php">Users</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        Edit
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-user-pen"></i> Edit User</h2>
      <p>Updating account for <strong><?= htmlspecialchars($user['full_name']) ?></strong></p>
    </div>

    <?php if ($error): ?>
    <div class="ha-alert ha-alert-danger"><i class="fas fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="ha-card" style="max-width:520px">
      <form method="POST">
        <div class="ha-form-group">
          <label class="ha-label">Full Name</label>
          <input type="text" name="full_name" class="ha-input"
                 value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>
        <div class="ha-form-group">
          <label class="ha-label">Email</label>
          <input type="email" name="email" class="ha-input"
                 value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="ha-form-group">
          <label class="ha-label">Phone Number</label>
          <input type="text" name="phone_number" class="ha-input"
                 value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
        </div>
        <div class="ha-form-group">
          <label class="ha-label">Role</label>
          <select name="role" class="ha-select">
            <option value="admin"   <?= $user['role']==='admin'   ?'selected':'' ?>>Admin</option>
            <option value="doctor"  <?= $user['role']==='doctor'  ?'selected':'' ?>>Doctor</option>
            <option value="patient" <?= $user['role']==='patient' ?'selected':'' ?>>Patient</option>
          </select>
        </div>
        <div style="display:flex;gap:10px;margin-top:8px">
          <button type="submit" class="ha-btn ha-btn-primary"><i class="fas fa-save"></i> Save Changes</button>
          <a href="users.php" class="ha-btn ha-btn-ghost"><i class="fas fa-arrow-left"></i> Cancel</a>
        </div>
      </form>
    </div>

  </main>
</div>
</body>
</html>