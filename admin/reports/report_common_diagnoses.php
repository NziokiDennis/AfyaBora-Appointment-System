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

$diagnoses = $conn->query("
    SELECT diagnosis, COUNT(*) AS count
    FROM medical_records
    WHERE diagnosis IS NOT NULL AND diagnosis != ''
    GROUP BY diagnosis ORDER BY count DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$labels = array_column($diagnoses,'diagnosis');
$data   = array_map('intval', array_column($diagnoses,'count'));
$total_records = $conn->query("SELECT COUNT(*) AS c FROM medical_records")->fetch_assoc()['c'];
$unique_diag   = $conn->query("SELECT COUNT(DISTINCT diagnosis) AS c FROM medical_records WHERE diagnosis IS NOT NULL AND diagnosis!=''")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Common Diagnoses — HealthAdmin</title>
</head>
<body>
<?php include "../sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Common Diagnoses</div>
      <div class="topbar-crumb">
        <a href="../dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="reports_dashboard.php">Reports</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        Diagnoses
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
      <h2><i class="fas fa-notes-medical"></i> Common Diagnoses</h2>
      <p>Top 10 diagnoses recorded across all medical records.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-file-medical"></i></div><div><div class="mini-stat-val"><?= $total_records ?></div><div class="mini-stat-lbl">Total Records</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-stethoscope"></i></div><div><div class="mini-stat-val"><?= $unique_diag ?></div><div class="mini-stat-lbl">Unique Diagnoses</div></div></div>
      <?php if(!empty($diagnoses)): ?>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-virus"></i></div><div><div class="mini-stat-val"><?= htmlspecialchars($diagnoses[0]['diagnosis']) ?></div><div class="mini-stat-lbl">Most Common</div></div></div>
      <?php endif; ?>
    </div>

    <?php if (empty($diagnoses)): ?>
    <div class="ha-alert ha-alert-info"><i class="fas fa-circle-info"></i> No diagnoses recorded yet.</div>
    <?php else: ?>

    <div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;margin-bottom:20px">
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-bar" style="color:var(--rose)"></i> Top 10 Diagnoses by Frequency
        </div>
        <canvas id="diagChart" style="max-height:280px"></canvas>
      </div>
      <div class="ha-card" style="display:flex;flex-direction:column">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-pie" style="color:var(--teal)"></i> Distribution Share
        </div>
        <div style="flex:1;display:flex;align-items:center;justify-content:center">
          <canvas id="pieChart" style="max-height:240px"></canvas>
        </div>
      </div>
    </div>

    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <i class="fas fa-list-ol" style="color:var(--teal)"></i> Ranked List
      </div>
      <table class="ha-table">
        <thead><tr><th>Rank</th><th>Diagnosis</th><th>Cases</th><th>Share</th><th>Visual</th></tr></thead>
        <tbody>
          <?php $max = $data[0] ?? 1; $total_diag = array_sum($data);
          foreach ($diagnoses as $i=>$d):
            $pct = $total_diag ? round($d['count']/$total_diag*100,1) : 0;
          ?>
          <tr>
            <td style="color:var(--muted);font-size:.72rem"><?= $i+1 ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($d['diagnosis']) ?></td>
            <td style="font-family:var(--font-mono);font-weight:700"><?= $d['count'] ?></td>
            <td style="color:var(--muted)"><?= $pct ?>%</td>
            <td style="width:150px">
              <div style="height:6px;border-radius:99px;background:var(--surface2)">
                <div style="height:100%;width:<?= round($d['count']/$max*100) ?>%;background:var(--rose);border-radius:99px"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

<?php if(!empty($diagnoses)): ?>
new Chart(document.getElementById('diagChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Cases',
      data: <?= json_encode($data) ?>,
      backgroundColor: <?= json_encode(array_map(fn($i) => 'rgba(240,91,112,'.round(0.4+0.06*$i,2).')', range(0,count($data)-1))) ?>,
      borderRadius: 5
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

new Chart(document.getElementById('pieChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{ data: <?= json_encode($data) ?>, backgroundColor: ['#f05b70','#f5a623','#0ec4a4','#3b7cff','#34c97d','#9b59b6','#e67e22','#1abc9c','#e74c3c','#3498db'], borderWidth: 0, hoverOffset: 6 }]
  },
  options: { responsive: true, cutout: '60%', plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>
</body>
</html>
