<?php
require_once "admin_auth.php";
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'patients';

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

$search = trim($_GET['search'] ?? '');
$where  = "WHERE u.role = 'patient'";
$params = [];
$types  = '';
if ($search !== '') {
    $like       = "%$search%";
    $genderTerm = rtrim(strtolower($search), 's'); // "males"/"Male" -> "male"
    if (in_array($genderTerm, ['male', 'female', 'other'], true)) {
        // "male" must not also match "female" via LIKE, so match gender exactly.
        $where  .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ? OR u.gender = ?)";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $genderTerm;
        $types   .= 'ssss';
    } else {
        $where  .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ?)";
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types   .= 'sss';
    }
}

$sql = "
    SELECT
        u.user_id AS patient_id,
        u.full_name,
        u.email,
        u.phone_number,
        u.date_of_birth,
        u.gender,
        u.address,
        u.next_of_kin_name,
        u.next_of_kin_relationship,
        u.next_of_kin_phone,
        u.created_at,
        COUNT(a.appointment_id) AS total_appointments,
        MAX(a.appointment_date) AS last_visit
    FROM users u
    LEFT JOIN appointments a ON u.user_id = a.patient_id
    $where
    GROUP BY u.user_id, u.full_name, u.email, u.phone_number, u.date_of_birth, u.gender, u.address, u.next_of_kin_name, u.next_of_kin_relationship, u.next_of_kin_phone, u.created_at
    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['export'])) {
    require_once "reports/export_helper.php";
    $headers = ['#','Name','Email','Phone','DOB','Gender','Address','Next of Kin','Relationship','Kin Phone','Appointments','Last Visit','Registered'];
    $rows = [];
    foreach ($patients as $i => $p) {
        $rows[] = [
            $i + 1,
            $p['full_name'],
            $p['email'],
            $p['phone_number'] ?? '',
            $p['date_of_birth'] ?? '',
            $p['gender'] ?? '',
            $p['address'] ?? '',
            $p['next_of_kin_name'] ?? '',
            $p['next_of_kin_relationship'] ?? '',
            $p['next_of_kin_phone'] ?? '',
            $p['total_appointments'],
            $p['last_visit'] ?? 'Never',
            date('M j, Y', strtotime($p['created_at'])),
        ];
    }
    export_and_exit($_GET['export'], 'patients', 'Patients Report', $headers, $rows);
}

$total_patients = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='patient'")->fetch_assoc()['c'];
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

    <div class="search-wrap" style="flex-wrap:wrap">
      <form method="GET" style="display:contents">
        <div class="search-input-wrap">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Search by name, email, phone, gender..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
        </div>
        <button type="submit" class="ha-btn ha-btn-primary ha-btn-sm"><i class="fas fa-search"></i> Search</button>
        <?php if($search): ?><a href="patients.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
      </form>
      <div class="ha-dropdown" style="position:relative;margin-left:auto">
        <button type="button" class="ha-btn ha-btn-ghost ha-btn-sm" onclick="document.getElementById('exportMenu').classList.toggle('show')"><i class="fas fa-download"></i> Export <i class="fas fa-caret-down"></i></button>
        <div id="exportMenu" class="show-menu" style="display:none;position:absolute;right:0;top:110%;background:var(--surface);border:1px solid var(--border);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.25);min-width:140px;z-index:20;overflow:hidden">
          <?php $exportQs = http_build_query(['search'=>$search]); ?>
          <a href="?<?= $exportQs ?>&export=pdf" style="display:block;padding:10px 14px;font-size:.82rem;color:var(--text);text-decoration:none"><i class="fas fa-file-pdf" style="color:var(--rose)"></i> PDF</a>
          <a href="?<?= $exportQs ?>&export=excel" style="display:block;padding:10px 14px;font-size:.82rem;color:var(--text);text-decoration:none"><i class="fas fa-file-excel" style="color:var(--green)"></i> Excel</a>
          <a href="?<?= $exportQs ?>&export=csv" style="display:block;padding:10px 14px;font-size:.82rem;color:var(--text);text-decoration:none"><i class="fas fa-file-csv" style="color:var(--blue)"></i> CSV</a>
        </div>
      </div>
    </div>
    <script>
    document.addEventListener('click', function(e){
      const menu = document.getElementById('exportMenu');
      if (!menu) return;
      if (!menu.parentElement.contains(e.target)) menu.classList.remove('show');
      menu.style.display = menu.classList.contains('show') ? 'block' : 'none';
    });
    </script>

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
            <th>Next of Kin</th>
            <th>Appointments</th>
            <th>Last Visit</th>
            <th>Registered</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($patients)): ?>
          <tr><td colspan="11" style="text-align:center;color:var(--muted);padding:32px">No patients found.</td></tr>
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
            <td>
              <?php if($p['next_of_kin_name']): ?>
              <div style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($p['next_of_kin_name']) ?></div>
              <div style="color:var(--muted);font-size:.72rem"><?= htmlspecialchars($p['next_of_kin_relationship'] ?? '') ?><?= $p['next_of_kin_phone'] ? ' · ' . htmlspecialchars($p['next_of_kin_phone']) : '' ?></div>
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