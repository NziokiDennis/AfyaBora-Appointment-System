<?php
require_once "admin_auth.php";
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'patients';

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

$search = trim($_GET['search'] ?? '');
$where  = '';
$params = [];
$types  = '';
if ($search !== '') {
    $where  = "WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ?";
    $like   = "%$search%";
    $params = [$like, $like, $like];
    $types  = 'sss';
}

$sql = "
    SELECT
        p.patient_id,
        u.full_name,
        u.email,
        u.phone_number,
        p.date_of_birth,
        p.gender,
        p.address,
        u.created_at,
        COUNT(a.appointment_id) AS total_appointments,
        MAX(a.appointment_date) AS last_visit
    FROM patients p
    JOIN users u ON p.user_id = u.user_id
    LEFT JOIN appointments a ON p.patient_id = a.patient_id
    $where
    GROUP BY p.patient_id, u.full_name, u.email, u.phone_number, p.date_of_birth, p.gender, p.address, u.created_at
    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_patients = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];
$today_new      = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='patient' AND DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patients — HealthAdmin</title>
</head>
<body>
<?php include "sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Patients</div>
      <div class="topbar-crumb"><a href="dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Patients</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="settings.php" class="topbar-icon-btn"><i class="fas fa-cog"></i></a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-user-injured"></i> Patients</h2>
      <p>All registered patients in the system.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-users"></i></div><div><div class="mini-stat-val"><?= $total_patients ?></div><div class="mini-stat-lbl">Total Patients</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-user-plus"></i></div><div><div class="mini-stat-val"><?= $today_new ?></div><div class="mini-stat-lbl">Joined Today</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-search"></i></div><div><div class="mini-stat-val"><?= count($patients) ?></div><div class="mini-stat-lbl">Showing</div></div></div>
    </div>

    <div class="search-wrap">
      <form method="GET" style="display:contents">
        <div class="search-input-wrap">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Search by name, email, phone..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
        </div>
        <button type="submit" class="ha-btn ha-btn-primary ha-btn-sm"><i class="fas fa-search"></i> Search</button>
        <?php if($search): ?><a href="patients.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="overflow-x:auto">
      <table class="ha-table" id="patientsTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>DOB</th>
            <th>Gender</th>
            <th>Address</th>
            <th>Appointments</th>
            <th>Last Visit</th>
            <th>Registered</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($patients)): ?>
          <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:32px">No patients found.</td></tr>
          <?php else: ?>
          <?php foreach ($patients as $i => $p): ?>
          <tr>
            <td style="color:var(--muted);font-size:.72rem"><?= $i+1 ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($p['full_name']) ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['phone_number'] ?? '—') ?></td>
            <td><?= $p['date_of_birth'] ?? '—' ?></td>
            <td><?= ucfirst($p['gender'] ?? '—') ?></td>
            <td>
              <?php if($p['address']): ?>
              <span class="ha-badge badge-scheduled"><?= htmlspecialchars($p['address']) ?></span>
              <?php else: echo '—'; endif; ?>
            </td>
            <td style="text-align:center;font-family:var(--font-mono);font-weight:600"><?= $p['total_appointments'] ?></td>
            <td style="color:var(--muted)"><?= $p['last_visit'] ?? 'Never' ?></td>
            <td style="color:var(--muted);font-size:.75rem"><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>

  </main>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('#patientsTable tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>