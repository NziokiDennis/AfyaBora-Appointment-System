<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'reports';

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];
$nr = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read=0");
$notif_count = (int)$nr->fetch_assoc()['c'];

// Monthly unique patients
$monthly_res = $conn->query("
    SELECT MONTHNAME(appointment_date) AS month, MONTH(appointment_date) AS m_num,
           COUNT(DISTINCT patient_id) AS unique_patients
    FROM appointments
    WHERE YEAR(appointment_date)=YEAR(CURDATE())
    GROUP BY m_num, month ORDER BY m_num
");
$m_labels=[]; $m_counts=[];
if ($monthly_res) while ($r=$monthly_res->fetch_assoc()) { $m_labels[]=$r['month']; $m_counts[]=(int)$r['unique_patients']; }

// Top patients by appointment count (filterable by name/email/phone and gender)
$search = trim($_GET['search'] ?? '');
$gender = trim($_GET['gender'] ?? '');
$where  = "WHERE u.role = 'patient'";
$params = [];
$types  = '';
if ($search !== '') {
    $where   .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}
if ($gender !== '' && in_array($gender, ['male','female','other'], true)) {
    $where   .= " AND u.gender = ?";
    $params[] = $gender;
    $types   .= 's';
}
$limit = ($search !== '' || $gender !== '') ? 1000 : 10;

$tp_sql = "
    SELECT u.full_name, u.gender,
           COUNT(a.appointment_id) AS total,
           SUM(a.status='completed') AS completed,
           MAX(a.appointment_date) AS last_visit
    FROM users u
    JOIN appointments a ON a.patient_id = u.user_id
    $where
    GROUP BY u.user_id
    ORDER BY total DESC
    LIMIT $limit
";
$tp_stmt = $conn->prepare($tp_sql);
if ($params) $tp_stmt->bind_param($types, ...$params);
$tp_stmt->execute();
$top_patients = $tp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['export'])) {
    require_once "export_helper.php";
    $headers = ['#','Patient','Gender','Total Visits','Completed','Last Visit'];
    $rows = [];
    foreach ($top_patients as $i => $p) {
        $rows[] = [$i+1, $p['full_name'], $p['gender'] ?? '', $p['total'], $p['completed'], $p['last_visit'] ?? 'Never'];
    }
    export_and_exit($_GET['export'], 'patient_engagement', 'Patient Engagement Report', $headers, $rows);
}

// Avg gap between appointments
$gaps = $conn->query("
    SELECT patient_id, appointment_date FROM appointments ORDER BY patient_id, appointment_date
")->fetch_all(MYSQLI_ASSOC);
$last=[]; $totalDays=0; $cnt=0;
foreach ($gaps as $r) {
    $pid=$r['patient_id']; $d=new DateTime($r['appointment_date']);
    if (isset($last[$pid])) { $totalDays+=$last[$pid]->diff($d)->days; $cnt++; }
    $last[$pid]=$d;
}
$avg_gap = $cnt ? round($totalDays/$cnt,1) : 0;

$total_patients = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='patient'")->fetch_assoc()['c'];
$returning = $conn->query("SELECT COUNT(DISTINCT patient_id) AS c FROM appointments GROUP BY patient_id HAVING COUNT(*)>1")->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Engagement — HealthAdmin</title>
</head>
<body>
<?php include "../sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Patient Engagement</div>
      <div class="topbar-crumb">
        <a href="../dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="reports_dashboard.php">Reports</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        Engagement
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?= date("D, M j Y") ?></div>
      <a href="../notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="reports_dashboard.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-arrow-left"></i> Reports</a>
    </div>
  </header>

  <main class="page-content">
    <div class="page-header">
      <h2><i class="fas fa-users"></i> Patient Engagement</h2>
      <p>Visit frequency, retention, and most active patients.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-users"></i></div><div><div class="mini-stat-val"><?= $total_patients ?></div><div class="mini-stat-lbl">Total Patients</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-rotate"></i></div><div><div class="mini-stat-val"><?= $returning ?></div><div class="mini-stat-lbl">Returning Patients</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-calendar-days"></i></div><div><div class="mini-stat-val"><?= $avg_gap ?> days</div><div class="mini-stat-lbl">Avg Visit Gap</div></div></div>
    </div>

    <div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;margin-bottom:20px">
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-bar" style="color:var(--amber)"></i> Monthly Unique Patients <?= date('Y') ?>
        </div>
        <canvas id="monthlyChart" style="max-height:230px"></canvas>
      </div>
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-bar" style="color:var(--blue)"></i> Top 5 Most Active
        </div>
        <canvas id="topChart" style="max-height:230px"></canvas>
      </div>
    </div>

    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px;margin-right:auto">
          <i class="fas fa-trophy" style="color:var(--amber)"></i> Most Frequent Patients
        </div>
        <form method="GET" style="display:flex;gap:8px;align-items:center;font-weight:400">
          <input type="text" name="search" placeholder="Search by name, email, phone..." value="<?= htmlspecialchars($search) ?>" style="padding:8px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:.8rem">
          <select name="gender" style="padding:8px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:.8rem">
            <option value="">All Genders</option>
            <option value="male"   <?= $gender==='male'   ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= $gender==='female' ? 'selected' : '' ?>>Female</option>
            <option value="other"  <?= $gender==='other'  ? 'selected' : '' ?>>Other</option>
          </select>
          <button type="submit" class="ha-btn ha-btn-primary ha-btn-sm"><i class="fas fa-search"></i></button>
          <?php if ($search || $gender): ?><a href="report_patient_engagement.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
        </form>
        <div style="position:relative">
          <button type="button" class="ha-btn ha-btn-ghost ha-btn-sm" onclick="document.getElementById('peExportMenu').classList.toggle('show')"><i class="fas fa-download"></i> Export <i class="fas fa-caret-down"></i></button>
          <div id="peExportMenu" style="display:none;position:absolute;right:0;top:110%;background:var(--surface);border:1px solid var(--border);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.25);min-width:140px;z-index:20;overflow:hidden">
            <?php $exportQs = http_build_query(['search'=>$search,'gender'=>$gender]); ?>
            <a href="?<?= $exportQs ?>&export=pdf" style="display:block;padding:10px 14px;font-size:.82rem;color:var(--text);text-decoration:none"><i class="fas fa-file-pdf" style="color:var(--rose)"></i> PDF</a>
            <a href="?<?= $exportQs ?>&export=excel" style="display:block;padding:10px 14px;font-size:.82rem;color:var(--text);text-decoration:none"><i class="fas fa-file-excel" style="color:var(--green)"></i> Excel</a>
            <a href="?<?= $exportQs ?>&export=csv" style="display:block;padding:10px 14px;font-size:.82rem;color:var(--text);text-decoration:none"><i class="fas fa-file-csv" style="color:var(--blue)"></i> CSV</a>
          </div>
        </div>
      </div>
      <table class="ha-table">
        <thead><tr><th>#</th><th>Patient</th><th>Gender</th><th>Total Visits</th><th>Completed</th><th>Last Visit</th><th>Activity</th></tr></thead>
        <tbody>
          <?php if (empty($top_patients)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:32px">No patients found.</td></tr>
          <?php else: $max = $top_patients[0]['total'] ?? 1; foreach($top_patients as $i=>$p): ?>
          <tr>
            <td style="color:var(--muted);font-size:.72rem"><?= $i+1 ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($p['full_name']) ?></td>
            <td><?= ucfirst($p['gender'] ?? '—') ?></td>
            <td style="font-family:var(--font-mono);font-weight:700"><?= $p['total'] ?></td>
            <td style="color:var(--green)"><?= $p['completed'] ?></td>
            <td style="color:var(--muted)"><?= $p['last_visit'] ?? '—' ?></td>
            <td style="width:120px">
              <div style="height:6px;border-radius:99px;background:var(--surface2)">
                <div style="height:100%;width:<?= round($p['total']/$max*100) ?>%;background:var(--teal);border-radius:99px"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <script>
    document.addEventListener('click', function(e){
      const menu = document.getElementById('peExportMenu');
      if (!menu) return;
      if (!menu.parentElement.contains(e.target)) menu.classList.remove('show');
      menu.style.display = menu.classList.contains('show') ? 'block' : 'none';
    });
    </script>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

const mctx = document.getElementById('monthlyChart').getContext('2d');
const mGrad = mctx.createLinearGradient(0,0,0,220);
mGrad.addColorStop(0,'rgba(245,166,35,.3)');
mGrad.addColorStop(1,'rgba(245,166,35,0)');
new Chart(mctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($m_labels) ?>,
    datasets: [{
      label: 'Unique Patients',
      data: <?= json_encode($m_counts) ?>,
      backgroundColor: 'rgba(245,166,35,.75)',
      borderRadius: 6
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

new Chart(document.getElementById('topChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column(array_slice($top_patients,0,5),'full_name')) ?>,
    datasets: [{
      label: 'Appointments',
      data: <?= json_encode(array_column(array_slice($top_patients,0,5),'total')) ?>,
      backgroundColor: 'rgba(59,124,255,.75)',
      borderRadius: 6
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});
</script>
</body>
</html>
