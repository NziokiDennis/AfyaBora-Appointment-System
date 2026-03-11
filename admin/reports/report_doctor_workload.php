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

$doctors = $conn->query("
    SELECT
        u.full_name,
        COUNT(a.appointment_id)        AS total,
        SUM(a.status='scheduled')      AS scheduled,
        SUM(a.status='completed')      AS completed,
        SUM(a.status='canceled')       AS canceled,
        SUM(a.status='no_show')        AS no_show,
        ROUND(AVG(f.rating),1)         AS avg_rating,
        COUNT(DISTINCT f.feedback_id)  AS feedback_count
    FROM users u
    LEFT JOIN appointments a ON u.user_id = a.doctor_id
    LEFT JOIN feedback f     ON u.user_id = f.doctor_id
    WHERE u.role = 'doctor'
    GROUP BY u.user_id, u.full_name
    ORDER BY total DESC
")->fetch_all(MYSQLI_ASSOC);

$names=[]; $totals=[]; $scheduled=[]; $completed=[]; $canceled=[];
foreach ($doctors as $d) {
    $names[]     = $d['full_name'];
    $totals[]    = (int)$d['total'];
    $scheduled[] = (int)$d['scheduled'];
    $completed[] = (int)$d['completed'];
    $canceled[]  = (int)$d['canceled'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Workload — HealthAdmin</title>
</head>
<body>
<?php include "../sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Doctor Workload</div>
      <div class="topbar-crumb">
        <a href="../dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="reports_dashboard.php">Reports</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        Workload
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
      <h2><i class="fas fa-user-md"></i> Doctor Workload</h2>
      <p>Appointment distribution, completion rates, and ratings per doctor.</p>
    </div>

    <div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;margin-bottom:20px">
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-bar" style="color:var(--blue)"></i> Appointment Breakdown by Doctor
        </div>
        <canvas id="workloadChart" style="max-height:260px"></canvas>
      </div>
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-circle-check" style="color:var(--green)"></i> Completion Rate
        </div>
        <canvas id="completionChart" style="max-height:260px"></canvas>
      </div>
    </div>

    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <i class="fas fa-table" style="color:var(--teal)"></i> Doctor Performance Table
      </div>
      <div style="overflow-x:auto">
      <table class="ha-table">
        <thead>
          <tr><th>Doctor</th><th>Total</th><th>Scheduled</th><th>Completed</th><th>Canceled</th><th>No-Show</th><th>Avg Rating</th><th>Completion %</th></tr>
        </thead>
        <tbody>
          <?php foreach($doctors as $d):
            $rate = $d['total'] ? round($d['completed']/$d['total']*100) : 0;
          ?>
          <tr>
            <td style="font-weight:600"><?= htmlspecialchars($d['full_name']) ?></td>
            <td style="font-family:var(--font-mono);font-weight:700"><?= $d['total'] ?></td>
            <td style="color:var(--blue)"><?= $d['scheduled'] ?></td>
            <td style="color:var(--green)"><?= $d['completed'] ?></td>
            <td style="color:var(--rose)"><?= $d['canceled'] ?></td>
            <td style="color:var(--muted)"><?= $d['no_show'] ?></td>
            <td>
              <?php if($d['avg_rating']): ?>
              <span style="color:var(--amber);font-weight:700"><i class="fas fa-star" style="font-size:.7rem"></i> <?= $d['avg_rating'] ?></span>
              <?php else: echo '<span style="color:var(--muted)">—</span>'; endif; ?>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div style="flex:1;height:6px;border-radius:99px;background:var(--surface2)">
                  <div style="height:100%;width:<?=$rate?>%;background:var(--green);border-radius:99px"></div>
                </div>
                <span style="font-size:.75rem;color:var(--muted);width:32px"><?=$rate?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

new Chart(document.getElementById('workloadChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($names) ?>,
    datasets: [
      { label: 'Scheduled',  data: <?= json_encode($scheduled) ?>, backgroundColor: 'rgba(59,124,255,.8)',  borderRadius: 4 },
      { label: 'Completed',  data: <?= json_encode($completed) ?>, backgroundColor: 'rgba(52,201,125,.8)',  borderRadius: 4 },
      { label: 'Canceled',   data: <?= json_encode($canceled)  ?>, backgroundColor: 'rgba(240,91,112,.8)',  borderRadius: 4 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top', labels: { boxWidth: 10, padding: 14, font: { size: 11 } } } },
    scales: { x: { stacked: false }, y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

new Chart(document.getElementById('completionChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($names) ?>,
    datasets: [{
      label: 'Completion %',
      data: <?= json_encode(array_map(fn($d) => $d['total'] ? round($d['completed']/$d['total']*100) : 0, $doctors)) ?>,
      backgroundColor: 'rgba(14,196,164,.75)',
      borderRadius: 6
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, max: 100, ticks: { callback: v => v+'%' } } }
  }
});
</script>
</body>
</html>
