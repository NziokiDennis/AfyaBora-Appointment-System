<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'reports';

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

$nr = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read = 0");
$notif_count = (int)$nr->fetch_assoc()['c'];

$total_appts    = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$canceled_count = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='canceled'")->fetch_assoc()['c'];
$noshow_count   = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='no_show'")->fetch_assoc()['c'];
$cancel_rate    = $total_appts ? round(($canceled_count/$total_appts)*100,1) : 0;
$noshow_rate    = $total_appts ? round(($noshow_count/$total_appts)*100,1) : 0;

// Monthly cancellations
$monthly = $conn->query("
    SELECT MONTHNAME(appointment_date) AS mon, MONTH(appointment_date) AS m_num,
           SUM(status='canceled') AS canceled, SUM(status='no_show') AS no_show
    FROM appointments
    WHERE YEAR(appointment_date)=YEAR(CURDATE())
    GROUP BY m_num, mon ORDER BY m_num
");
$m_labels=[]; $m_canceled=[]; $m_noshow=[];
if($monthly){ while($r=$monthly->fetch_assoc()){
    $m_labels[]=$r['mon']; $m_canceled[]=(int)$r['canceled']; $m_noshow[]=(int)$r['no_show'];
}}

// By doctor
$by_doc = $conn->query("
    SELECT u.full_name, COUNT(*) AS cnt,
           SUM(a.status='canceled') AS canceled, SUM(a.status='no_show') AS no_show
    FROM users u
    JOIN appointments a ON u.user_id=a.doctor_id
    WHERE u.role='doctor'
    GROUP BY u.user_id ORDER BY canceled DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cancellation Analysis — HealthAdmin</title>
<?php include "../sidebar.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Cancellation Analysis</div>
      <div class="topbar-crumb"><a href="../dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> <a href="reports_dashboard.php">Reports</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Cancellations</div>
    </div>
    <div class="topbar-right">
      <a href="reports_dashboard.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-arrow-left"></i> All Reports</a>
    </div>
  </header>

  <main class="page-content">
    <div class="page-header">
      <h2><i class="fas fa-calendar-xmark"></i> Cancellation Analysis</h2>
      <p>Understand cancellation and no-show patterns to reduce revenue leakage.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-ban"></i></div><div><div class="mini-stat-val"><?= $canceled_count ?></div><div class="mini-stat-lbl">Total Canceled</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-person-walking-arrow-right"></i></div><div><div class="mini-stat-val"><?= $noshow_count ?></div><div class="mini-stat-lbl">No-Shows</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-percent"></i></div><div><div class="mini-stat-val"><?= $cancel_rate ?>%</div><div class="mini-stat-lbl">Cancellation Rate</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-percent"></i></div><div><div class="mini-stat-val"><?= $noshow_rate ?>%</div><div class="mini-stat-lbl">No-Show Rate</div></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px"><i class="fas fa-chart-bar" style="color:var(--teal)"></i> Monthly Cancellations & No-Shows (<?= date('Y') ?>)</div>
        <canvas id="monthlyChart" style="max-height:230px"></canvas>
      </div>
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px"><i class="fas fa-user-md" style="color:var(--teal)"></i> By Doctor</div>
        <table class="ha-table">
          <thead><tr><th>Doctor</th><th>Canceled</th><th>No-Show</th></tr></thead>
          <tbody>
            <?php foreach($by_doc as $d): ?>
            <tr>
              <td style="font-weight:500"><?= htmlspecialchars($d['full_name']) ?></td>
              <td style="color:var(--rose);font-weight:700"><?= $d['canceled'] ?></td>
              <td style="color:var(--amber);font-weight:700"><?= $d['no_show'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = 'Plus Jakarta Sans';

new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($m_labels) ?>,
    datasets: [
      { label: 'Canceled', data: <?= json_encode($m_canceled) ?>, backgroundColor: 'rgba(240,91,112,.7)', borderRadius: 5 },
      { label: 'No-Show',  data: <?= json_encode($m_noshow)   ?>, backgroundColor: 'rgba(245,166,35,.7)', borderRadius: 5 }
    ]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { position: 'top' } } }
});
</script>
</body>
</html>