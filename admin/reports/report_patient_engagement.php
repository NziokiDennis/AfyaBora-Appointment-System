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

// Top 10 patients by appointment count
$top_patients = $conn->query("
    SELECT u.full_name,
           COUNT(a.appointment_id) AS total,
           SUM(a.status='completed') AS completed,
           MAX(a.appointment_date) AS last_visit
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    GROUP BY a.patient_id
    ORDER BY total DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

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

$total_patients = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];
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
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <i class="fas fa-trophy" style="color:var(--amber)"></i> Most Frequent Patients
      </div>
      <table class="ha-table">
        <thead><tr><th>#</th><th>Patient</th><th>Total Visits</th><th>Completed</th><th>Last Visit</th><th>Activity</th></tr></thead>
        <tbody>
          <?php $max = $top_patients[0]['total'] ?? 1; foreach($top_patients as $i=>$p): ?>
          <tr>
            <td style="color:var(--muted);font-size:.72rem"><?= $i+1 ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($p['full_name']) ?></td>
            <td style="font-family:var(--font-mono);font-weight:700"><?= $p['total'] ?></td>
            <td style="color:var(--green)"><?= $p['completed'] ?></td>
            <td style="color:var(--muted)"><?= $p['last_visit'] ?? '—' ?></td>
            <td style="width:120px">
              <div style="height:6px;border-radius:99px;background:var(--surface2)">
                <div style="height:100%;width:<?= round($p['total']/$max*100) ?>%;background:var(--teal);border-radius:99px"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
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
