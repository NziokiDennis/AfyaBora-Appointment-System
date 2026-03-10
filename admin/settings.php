<?php
require_once "admin_auth.php";
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'settings';
$admin_id     = $_SESSION["admin_id"];

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

$nr = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read = 0");
$notif_count = (int)$nr->fetch_assoc()['c'];

// Fetch admin data
$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

$success = $errors = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($username))  $errors[] = "Username is required.";

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE admins SET full_name=?, username=?, email=?, phone=? WHERE admin_id=?");
        $upd->bind_param('ssssi', $full_name, $username, $email, $phone, $admin_id);
        if ($upd->execute()) {
            $_SESSION['full_name'] = $full_name;
            $admin_name = $full_name;
            $success[] = "Profile updated successfully.";
            // re-fetch
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Update failed: " . $conn->error;
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pw  = $_POST['current_password'];
    $new_pw      = $_POST['new_password'];
    $confirm_pw  = $_POST['confirm_password'];

    $match = ($current_pw === $admin['password_hash']) || password_verify($current_pw, $admin['password_hash']);
    if (!$match) {
        $errors[] = "Current password is incorrect.";
    } elseif (strlen($new_pw) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif ($new_pw !== $confirm_pw) {
        $errors[] = "New passwords do not match.";
    } else {
        $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE admins SET password_hash=? WHERE admin_id=?");
        $upd->bind_param('si', $new_hash, $admin_id);
        if ($upd->execute()) {
            $success[] = "Password changed successfully.";
        } else {
            $errors[] = "Password update failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — HealthAdmin</title>
<?php include "sidebar.php"; ?>
<style>
.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:800px) { .settings-grid { grid-template-columns: 1fr; } }
.avatar-display {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, var(--teal), var(--blue));
  display: flex; align-items: center; justify-content: center;
  font-size: 1.6rem; font-weight: 700; color: #fff;
  margin-bottom: 12px;
  box-shadow: 0 0 0 4px var(--surface), 0 0 0 6px var(--teal);
}
.settings-section-title {
  font-size: .75rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .09em;
  color: var(--muted); margin-bottom: 18px;
  padding-bottom: 10px; border-bottom: 1px solid var(--border);
}
</style>
</head>
<body>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Settings</div>
      <div class="topbar-crumb"><a href="dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Settings</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-cog"></i> Settings</h2>
      <p>Manage your admin profile and account security.</p>
    </div>

    <?php foreach($success as $msg): ?>
    <div class="ha-alert ha-alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>
    <?php foreach($errors as $msg): ?>
    <div class="ha-alert ha-alert-danger"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>

    <!-- Profile card top -->
    <div class="ha-card" style="display:flex;align-items:center;gap:20px;margin-bottom:20px">
      <div class="avatar-display"><?= strtoupper(substr($admin['full_name'] ?? 'A', 0, 2)) ?></div>
      <div>
        <div style="font-size:1.1rem;font-weight:700"><?= htmlspecialchars($admin['full_name'] ?? '') ?></div>
        <div style="color:var(--teal);font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;margin-top:2px">Administrator</div>
        <div style="color:var(--muted);font-size:.78rem;margin-top:4px"><i class="fas fa-user" style="margin-right:5px"></i><?= htmlspecialchars($admin['username'] ?? '') ?></div>
      </div>
      <div style="margin-left:auto;text-align:right">
        <div style="font-size:.72rem;color:var(--muted)">Admin ID</div>
        <div style="font-family:var(--font-mono);font-weight:700;color:var(--teal)">#<?= $admin_id ?></div>
      </div>
    </div>

    <div class="settings-grid">

      <!-- Profile form -->
      <div class="ha-card">
        <div class="settings-section-title"><i class="fas fa-user" style="margin-right:6px"></i>Profile Information</div>
        <form method="POST">
          <input type="hidden" name="update_profile" value="1">
          <div class="ha-form-group">
            <label class="ha-label">Full Name</label>
            <input type="text" name="full_name" class="ha-input" value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" required>
          </div>
          <div class="ha-form-group">
            <label class="ha-label">Username</label>
            <input type="text" name="username" class="ha-input" value="<?= htmlspecialchars($admin['username'] ?? '') ?>" required>
          </div>
          <div class="ha-form-group">
            <label class="ha-label">Email Address</label>
            <input type="email" name="email" class="ha-input" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" placeholder="admin@example.com">
          </div>
          <div class="ha-form-group">
            <label class="ha-label">Phone Number</label>
            <input type="text" name="phone" class="ha-input" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>" placeholder="+254 ...">
          </div>
          <button type="submit" class="ha-btn ha-btn-primary" style="width:100%;justify-content:center">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </form>
      </div>

      <!-- Password form -->
      <div>
        <div class="ha-card" style="margin-bottom:0">
          <div class="settings-section-title"><i class="fas fa-lock" style="margin-right:6px"></i>Change Password</div>
          <form method="POST">
            <input type="hidden" name="change_password" value="1">
            <div class="ha-form-group">
              <label class="ha-label">Current Password</label>
              <input type="password" name="current_password" class="ha-input" placeholder="Enter current password" required>
            </div>
            <div class="ha-form-group">
              <label class="ha-label">New Password</label>
              <input type="password" name="new_password" class="ha-input" placeholder="Min. 6 characters" required id="newPw">
            </div>
            <div class="ha-form-group">
              <label class="ha-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="ha-input" placeholder="Repeat new password" required id="confirmPw">
              <div id="pwMatch" style="font-size:.72rem;margin-top:5px"></div>
            </div>
            <button type="submit" class="ha-btn ha-btn-primary" style="width:100%;justify-content:center">
              <i class="fas fa-key"></i> Update Password
            </button>
          </form>
        </div>

        <!-- System info card -->
        <div class="ha-card" style="margin-top:20px">
          <div class="settings-section-title"><i class="fas fa-circle-info" style="margin-right:6px"></i>System Info</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <?php
            $info = [
              ['System', 'Bilpham Outpatient Admin'],
              ['Version', 'v1.0.0'],
              ['PHP Version', PHP_VERSION],
              ['Server Time', date('Y-m-d H:i:s')],
              ['Session ID', substr(session_id(),0,12).'...'],
            ];
            foreach($info as [$label, $val]): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
              <span style="font-size:.78rem;color:var(--muted)"><?=$label?></span>
              <span style="font-size:.78rem;font-family:var(--font-mono);color:var(--text)"><?=htmlspecialchars($val)?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>

  </main>
</div>

<script>
const newPw = document.getElementById('newPw');
const confirmPw = document.getElementById('confirmPw');
const matchMsg  = document.getElementById('pwMatch');

function checkMatch() {
  if (!confirmPw.value) { matchMsg.textContent = ''; return; }
  if (newPw.value === confirmPw.value) {
    matchMsg.textContent = '✓ Passwords match'; matchMsg.style.color = 'var(--green)';
  } else {
    matchMsg.textContent = '✗ Passwords do not match'; matchMsg.style.color = 'var(--rose)';
  }
}
newPw.addEventListener('input', checkMatch);
confirmPw.addEventListener('input', checkMatch);
</script>
</body>
</html>