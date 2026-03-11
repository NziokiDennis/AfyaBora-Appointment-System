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

$period = $_GET['period'] ?? 'month';

switch ($period) {
    case 'day':
        $query = "SELECT DATE_FORMAT(appointment_date,'%b %d') AS label, COUNT(*) AS count
                  FROM appointments WHERE appointment_date >= CURDATE() - INTERVAL 30 DAY
                  GROUP BY DATE(appointment_date) ORDER BY DATE(appointment_date)";
        $period_label = 'Last 30 Days';
        break;
    case 'week':
        $query = "SELECT CONCAT('Wk ',WEEK(appointment_date)) AS label, COUNT(*) AS count
                  FROM appointments WHERE YEAR(appointment_date)=YEAR(CURDATE())
                  GROUP BY WEEK(appointment_date) ORDER BY WEEK(appointment_date)";
        $period_label = 'Weekly (This Year)';
        break;
    case 'year':
        $query = "SELECT YEAR(appointment_date) AS label, COUNT(*) AS count
                  FROM appointments GROUP BY YEAR(appointment_date) ORDER BY label";
        $period_label = 'Yearly';
        break;
    default:
        $query = "SELECT MONTHNAME(appointment_date) AS label, MONTH(appointment_date) AS m_num, COUNT(*) AS count
                  FROM appointments WHERE YEAR(appointment_date)=YEAR(CURDATE())
                  GROUP BY m_num, label ORDER BY m_num";
        $period_label = 'Monthly (This Year)';
}

$res = $conn->query($query);
$labels = []; $data = [];
if ($res) while ($r = $res->fetch_assoc()) { $labels[] = $r['label']; $data[] = (int)$r['count']; }

// Status breakdown
$statuses = ['scheduled'=>0,'completed'=>0,'canceled'=>0,'no_show'=>0,'rescheduled'=>0];
$sr = $conn->query("SELECT status, COUNT(*) AS c FROM appointments GROUP BY status");
if ($sr) while ($r = $sr->fetch_assoc()) if (isset($statuses[$r['status']])) $statuses[$r['status']] = (int)$r['c'];

$total = array_sum($statuses);
$completion_rate = $total ? round($statuses['completed']/$total*100,1) : 0;
$cancel_rate     = $total ? round($statuses['canceled']/$total*100,1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointment Trends — HealthAdmin</title>
</head>
<body>
<?php include "../sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Appointment Trends</div>
      <div class="topbar-crumb">
        <a href="../dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="reports_dashboard.php">Reports</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        Trends
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
      <h2><i class="fas fa-chart-line"></i> Appointment Trends</h2>
      <p>Booking volumes and status distribution over time.</p>
    </div>

    <!-- KPIs -->
    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-calendar-check"></i></div><div><div class="mini-stat-val"><?= $total ?></div><div class="mini-stat-lbl">Total Appointments</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-clock"></i></div><div><div class="mini-stat-val"><?= $statuses['scheduled'] ?></div><div class="mini-stat-lbl">Scheduled</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-circle-check"></i></div><div><div class="mini-stat-val"><?= $completion_rate ?>%</div><div class="mini-stat-lbl">Completion Rate</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-ban"></i></div><div><div class="mini-stat-val"><?= $cancel_rate ?>%</div><div class="mini-stat-lbl">Cancellation Rate</div></div></div>
    </div>

    <!-- Period filters -->
    <div style="display:flex;gap:8px;margin-bottom:20px">
      <?php foreach(['day'=>'Daily','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly'] as $v=>$l): ?>
      <a href="?period=<?=$v?>" class="ha-btn ha-btn-sm <?= $period===$v ? 'ha-btn-primary' : 'ha-btn-ghost' ?>"><?=$l?></a>
      <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
      <!-- Trend line -->
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:7px;color:var(--text)">
          <i class="fas fa-chart-line" style="color:var(--teal)"></i> Appointments Over Time
          <span style="margin-left:auto;font-size:.72rem;color:var(--muted);font-weight:400"><?= $period_label ?></span>
        </div>
        <canvas id="trendChart" style="max-height:240px;margin-top:14px"></canvas>
      </div>

      <!-- Donut breakdown -->
      <div class="ha-card" style="display:flex;flex-direction:column">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-donut" style="color:var(--teal)"></i> Status Breakdown
        </div>
        <div style="flex:1;display:flex;align-items:center;justify-content:center">
          <canvas id="statusChart" style="max-height:200px"></canvas>
        </div>
      </div>
    </div>

    <!-- Status table -->
    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <i class="fas fa-table-list" style="color:var(--teal)"></i> Status Summary
      </div>
      <table class="ha-table">
        <thead><tr><th>Status</th><th>Count</th><th>Share</th><th>Visual</th></tr></thead>
        <tbody>
          <?php
          $status_colors = ['scheduled'=>'var(--blue)','completed'=>'var(--green)','canceled'=>'var(--rose)','no_show'=>'var(--muted)','rescheduled'=>'var(--amber)'];
          foreach ($statuses as $s => $c):
            $pct = $total ? round($c/$total*100,1) : 0;
          ?>
          <tr>
            <td><span class="ha-badge badge-<?= $s ?>"><?= ucfirst(str_replace('_',' ',$s)) ?></span></td>
            <td style="font-family:var(--font-mono);font-weight:700"><?= $c ?></td>
            <td style="color:var(--muted)"><?= $pct ?>%</td>
            <td style="width:160px">
              <div style="height:6px;border-radius:99px;background:var(--surface2);overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $status_colors[$s] ?>;border-radius:99px"></div>
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

// Trend chart
const tctx = document.getElementById('trendChart').getContext('2d');
const tGrad = tctx.createLinearGradient(0,0,0,240);
tGrad.addColorStop(0,'rgba(14,196,164,.25)');
tGrad.addColorStop(1,'rgba(14,196,164,0)');
new Chart(tctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      data: <?= json_encode($data) ?>,
      fill: true,
      backgroundColor: tGrad,
      borderColor: '#0ec4a4',
      borderWidth: 2.5,
      tension: 0.4,
      pointBackgroundColor: '#0ec4a4',
      pointRadius: 4,
      pointHoverRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

// Status donut
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: ['Scheduled','Completed','Canceled','No-Show','Rescheduled'],
    datasets: [{
      data: [<?= $statuses['scheduled'] ?>,<?= $statuses['completed'] ?>,<?= $statuses['canceled'] ?>,<?= $statuses['no_show'] ?>,<?= $statuses['rescheduled'] ?>],
      backgroundColor: ['#3b7cff','#34c97d','#f05b70','#6b7280','#f5a623'],
      borderWidth: 0,
      hoverOffset: 6
    }]
  },
  options: {
    responsive: true,
    cutout: '68%',
    plugins: { legend: { position: 'bottom', labels: { padding: 14, boxWidth: 10, font: { size: 11 } } } }
  }
});
</script>
</body>
</html>
