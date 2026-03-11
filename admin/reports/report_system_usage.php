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

$total_patients     = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='patient'")->fetch_assoc()['c'];
$total_doctors      = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='doctor'")->fetch_assoc()['c'];
$total_admins       = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$total_appointments = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$total_feedback     = $conn->query("SELECT COUNT(*) AS c FROM feedback")->fetch_assoc()['c'];
$total_records      = $conn->query("SELECT COUNT(*) AS c FROM medical_records")->fetch_assoc()['c'];

$statuses = ['scheduled'=>0,'completed'=>0,'canceled'=>0,'no_show'=>0,'rescheduled'=>0];
$sr = $conn->query("SELECT status, COUNT(*) AS c FROM appointments GROUP BY status");
if ($sr) while ($r=$sr->fetch_assoc()) if (isset($statuses[$r['status']])) $statuses[$r['status']]=(int)$r['c'];

// New users per month this year
$new_users_res = $conn->query("
    SELECT MONTHNAME(created_at) AS mon, MONTH(created_at) AS m_num, COUNT(*) AS c
    FROM users WHERE YEAR(created_at)=YEAR(CURDATE())
    GROUP BY m_num, mon ORDER BY m_num
");
$nu_labels=[]; $nu_data=[];
if ($new_users_res) while ($r=$new_users_res->fetch_assoc()) { $nu_labels[]=$r['mon']; $nu_data[]=(int)$r['c']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Usage — HealthAdmin</title>
</head>
<body>
<?php include "../sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">System Usage</div>
      <div class="topbar-crumb">
        <a href="../dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="reports_dashboard.php">Reports</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        System Usage
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
      <h2><i class="fas fa-chart-pie"></i> System Usage Overview</h2>
      <p>Platform-wide metrics — users, records, appointments and feature activity.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-user-injured"></i></div><div><div class="mini-stat-val"><?= $total_patients ?></div><div class="mini-stat-lbl">Patients</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-user-md"></i></div><div><div class="mini-stat-val"><?= $total_doctors ?></div><div class="mini-stat-lbl">Doctors</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-calendar-check"></i></div><div><div class="mini-stat-val"><?= $total_appointments ?></div><div class="mini-stat-lbl">Appointments</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-comment-dots"></i></div><div><div class="mini-stat-val"><?= $total_feedback ?></div><div class="mini-stat-lbl">Feedback</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-file-medical"></i></div><div><div class="mini-stat-val"><?= $total_records ?></div><div class="mini-stat-lbl">Medical Records</div></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-line" style="color:var(--teal)"></i> New User Registrations <?= date('Y') ?>
        </div>
        <canvas id="usersChart" style="max-height:230px"></canvas>
      </div>
      <div class="ha-card" style="display:flex;flex-direction:column">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-pie" style="color:var(--blue)"></i> Appointment Status
        </div>
        <div style="flex:1;display:flex;align-items:center;justify-content:center">
          <canvas id="statusChart" style="max-height:200px"></canvas>
        </div>
      </div>
    </div>

    <!-- User mix -->
    <div class="ha-card">
      <div style="font-size:.82rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:7px">
        <i class="fas fa-users" style="color:var(--teal)"></i> User Mix
      </div>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <?php
        $roles = ['patient'=>[$total_patients,'var(--green)'],'doctor'=>[$total_doctors,'var(--blue)'],'admin'=>[$total_admins,'var(--rose)']];
        $total_users = $total_patients+$total_doctors+$total_admins;
        foreach ($roles as $role=>[$count,$color]):
          $pct = $total_users ? round($count/$total_users*100) : 0;
        ?>
        <div style="flex:1;min-width:140px">
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.8rem">
            <span style="font-weight:600;text-transform:capitalize"><?= $role ?>s</span>
            <span style="color:var(--muted)"><?= $count ?> (<?= $pct ?>%)</span>
          </div>
          <div style="height:8px;border-radius:99px;background:var(--surface2)">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:99px"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

const uctx = document.getElementById('usersChart').getContext('2d');
const uGrad = uctx.createLinearGradient(0,0,0,220);
uGrad.addColorStop(0,'rgba(14,196,164,.25)');
uGrad.addColorStop(1,'rgba(14,196,164,0)');
new Chart(uctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($nu_labels) ?>,
    datasets: [{
      data: <?= json_encode($nu_data) ?>,
      fill: true, backgroundColor: uGrad,
      borderColor: '#0ec4a4', borderWidth: 2.5,
      tension: 0.4, pointBackgroundColor: '#0ec4a4', pointRadius: 4
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: ['Scheduled','Completed','Canceled','No-Show','Rescheduled'],
    datasets: [{
      data: [<?= $statuses['scheduled'] ?>,<?= $statuses['completed'] ?>,<?= $statuses['canceled'] ?>,<?= $statuses['no_show'] ?>,<?= $statuses['rescheduled'] ?>],
      backgroundColor: ['#3b7cff','#34c97d','#f05b70','#6b7280','#f5a623'],
      borderWidth: 0, hoverOffset: 6
    }]
  },
  options: { responsive: true, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { padding: 12, boxWidth: 10, font: { size: 11 } } } } }
});
</script>
</body>
</html>
