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

$total         = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$canceled      = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='canceled'")->fetch_assoc()['c'];
$noshow        = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='no_show'")->fetch_assoc()['c'];
$cancel_rate   = $total ? round($canceled/$total*100,1) : 0;
$noshow_rate   = $total ? round($noshow/$total*100,1) : 0;

// Monthly
$monthly = $conn->query("
    SELECT MONTHNAME(appointment_date) AS mon, MONTH(appointment_date) AS m_num,
           SUM(status='canceled') AS canceled, SUM(status='no_show') AS no_show
    FROM appointments WHERE YEAR(appointment_date)=YEAR(CURDATE())
    GROUP BY m_num, mon ORDER BY m_num
")->fetch_all(MYSQLI_ASSOC);
$m_labels=array_column($monthly,'mon');
$m_canceled=array_map('intval',array_column($monthly,'canceled'));
$m_noshow=array_map('intval',array_column($monthly,'no_show'));

// By doctor
$by_doc = $conn->query("
    SELECT u.full_name,
           COUNT(a.appointment_id) AS total,
           SUM(a.status='canceled') AS canceled,
           SUM(a.status='no_show') AS no_show
    FROM users u
    JOIN appointments a ON u.user_id=a.doctor_id
    WHERE u.role='doctor'
    GROUP BY u.user_id ORDER BY canceled DESC
")->fetch_all(MYSQLI_ASSOC);

// Most canceled reason
$reasons = $conn->query("
    SELECT reason, COUNT(*) AS c FROM appointments WHERE status='canceled' AND reason IS NOT NULL AND reason!=''
    GROUP BY reason ORDER BY c DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cancellation Analysis — HealthAdmin</title>
</head>
<body>
<?php include "../sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Cancellation Analysis</div>
      <div class="topbar-crumb">
        <a href="../dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="reports_dashboard.php">Reports</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        Cancellations
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
      <h2><i class="fas fa-calendar-xmark"></i> Cancellation Analysis</h2>
      <p>Identify cancellation and no-show patterns to reduce revenue leakage.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-ban"></i></div><div><div class="mini-stat-val"><?= $canceled ?></div><div class="mini-stat-lbl">Canceled</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-person-walking-arrow-right"></i></div><div><div class="mini-stat-val"><?= $noshow ?></div><div class="mini-stat-lbl">No-Shows</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-percent"></i></div><div><div class="mini-stat-val"><?= $cancel_rate ?>%</div><div class="mini-stat-lbl">Cancellation Rate</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-percent"></i></div><div><div class="mini-stat-val"><?= $noshow_rate ?>%</div><div class="mini-stat-lbl">No-Show Rate</div></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-bar" style="color:var(--rose)"></i> Monthly Cancellations & No-Shows <?= date('Y') ?>
        </div>
        <canvas id="monthlyChart" style="max-height:240px"></canvas>
      </div>
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-user-md" style="color:var(--teal)"></i> By Doctor
        </div>
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

    <?php if (!empty($reasons)): ?>
    <div class="ha-card">
      <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
        <i class="fas fa-tags" style="color:var(--amber)"></i> Top Cancellation Reasons
      </div>
      <?php $max_r = $reasons[0]['c'] ?? 1; foreach($reasons as $r): ?>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
        <span style="font-size:.82rem;width:160px;flex-shrink:0"><?= htmlspecialchars($r['reason']) ?></span>
        <div style="flex:1;height:8px;border-radius:99px;background:var(--surface2)">
          <div style="height:100%;width:<?= round($r['c']/$max_r*100) ?>%;background:var(--rose);border-radius:99px"></div>
        </div>
        <span style="font-family:var(--font-mono);font-weight:700;font-size:.8rem;width:24px;text-align:right"><?= $r['c'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($m_labels) ?>,
    datasets: [
      { label: 'Canceled', data: <?= json_encode($m_canceled) ?>, backgroundColor: 'rgba(240,91,112,.75)', borderRadius: 5 },
      { label: 'No-Show',  data: <?= json_encode($m_noshow)   ?>, backgroundColor: 'rgba(245,166,35,.75)',  borderRadius: 5 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top', labels: { boxWidth: 10, padding: 14, font: { size: 11 } } } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});
</script>
</body>
</html>
