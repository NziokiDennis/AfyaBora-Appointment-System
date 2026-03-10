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

// Quick stats for report overview
$total_revenue  = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE payment_status='paid'")->fetch_assoc()['t'];
$pending_pay    = $conn->query("SELECT COUNT(*) AS c FROM payments WHERE payment_status='pending'")->fetch_assoc()['c'];
$total_records  = $conn->query("SELECT COUNT(*) AS c FROM medical_records")->fetch_assoc()['c'];
$avg_rating     = $conn->query("SELECT ROUND(AVG(rating),1) AS r FROM feedback")->fetch_assoc()['r'] ?? 'N/A';

$reports = [
    [
        'title'       => 'Appointment Trends',
        'desc'        => 'Daily, weekly, monthly and yearly appointment volumes. Spot peak periods and booking patterns.',
        'icon'        => 'fas fa-chart-line',
        'color'       => 'teal',
        'href'        => 'report_appointment_trends.php',
        'tag'         => 'Operations',
    ],
    [
        'title'       => 'Payments & Revenue',
        'desc'        => 'Revenue overview, payment status breakdown, outstanding balances and payment trends over time.',
        'icon'        => 'fas fa-money-bill-wave',
        'color'       => 'green',
        'href'        => 'report_payments.php',
        'tag'         => 'Financial',
    ],
    [
        'title'       => 'Doctor Workload',
        'desc'        => 'Appointment distribution across doctors. Compare scheduled, completed and canceled rates per doctor.',
        'icon'        => 'fas fa-user-md',
        'color'       => 'blue',
        'href'        => 'report_doctor_workload.php',
        'tag'         => 'Performance',
    ],
    [
        'title'       => 'Patient Engagement',
        'desc'        => 'New vs returning patients, visit frequency, average gap between visits, and retention metrics.',
        'icon'        => 'fas fa-users',
        'color'       => 'amber',
        'href'        => 'report_patient_engagement.php',
        'tag'         => 'Patients',
    ],
    [
        'title'       => 'Feedback & Satisfaction',
        'desc'        => 'Patient satisfaction scores by doctor. Rating distributions and feedback trends over time.',
        'icon'        => 'fas fa-star',
        'color'       => 'amber',
        'href'        => 'report_feedback_satisfaction.php',
        'tag'         => 'Quality',
    ],
    [
        'title'       => 'Common Diagnoses',
        'desc'        => 'Top 10 diagnoses recorded in medical records. Identify prevalent conditions in your patient base.',
        'icon'        => 'fas fa-notes-medical',
        'color'       => 'rose',
        'href'        => 'report_common_diagnoses.php',
        'tag'         => 'Clinical',
    ],
    [
        'title'       => 'Cancellation Analysis',
        'desc'        => 'Cancellation and no-show rates over time, by doctor, and by time slot. Reduce revenue leakage.',
        'icon'        => 'fas fa-calendar-xmark',
        'color'       => 'rose',
        'href'        => 'report_cancellations.php',
        'tag'         => 'Operations',
    ],
    [
        'title'       => 'System Usage Overview',
        'desc'        => 'Overall platform metrics — users, records, appointments, and feature adoption at a glance.',
        'icon'        => 'fas fa-chart-pie',
        'color'       => 'blue',
        'href'        => 'report_system_usage.php',
        'tag'         => 'System',
    ],
];

$color_map = [
    'teal'  => ['bg'=>'var(--teal-glow)',             'color'=>'var(--teal)'],
    'green' => ['bg'=>'rgba(52,201,125,.15)',          'color'=>'var(--green)'],
    'blue'  => ['bg'=>'rgba(59,124,255,.15)',          'color'=>'var(--blue)'],
    'amber' => ['bg'=>'rgba(245,166,35,.15)',          'color'=>'var(--amber)'],
    'rose'  => ['bg'=>'rgba(240,91,112,.15)',          'color'=>'var(--rose)'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports — HealthAdmin</title>
<?php include "../sidebar.php"; ?>
<style>
.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}
.report-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px;
  text-decoration: none; color: var(--text);
  display: flex; flex-direction: column; gap: 10px;
  transition: all .2s;
  position: relative; overflow: hidden;
}
.report-card:hover {
  border-color: rgba(255,255,255,.12);
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(0,0,0,.3);
}
.report-card:hover .report-arrow { opacity: 1; transform: translateX(0); }
.report-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.report-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
.report-tag { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; padding: 3px 8px; border-radius: 99px; }
.report-title { font-size: .95rem; font-weight: 700; }
.report-desc  { font-size: .78rem; color: var(--muted); line-height: 1.5; flex: 1; }
.report-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
.report-cta   { font-size: .78rem; font-weight: 600; display: flex; align-items: center; gap: 5px; }
.report-arrow { opacity: 0; transform: translateX(-4px); transition: all .2s; }
</style>
</head>
<body>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Reports</div>
      <div class="topbar-crumb"><a href="../dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Reports</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="../notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="../settings.php" class="topbar-icon-btn"><i class="fas fa-cog"></i></a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
      <p>Data insights across appointments, revenue, clinical records and system performance.</p>
    </div>

    <!-- KPI strip -->
    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-money-bill-wave"></i></div><div><div class="mini-stat-val" style="font-size:1.1rem">KES <?= number_format($total_revenue,2) ?></div><div class="mini-stat-lbl">Total Revenue</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-clock"></i></div><div><div class="mini-stat-val"><?= $pending_pay ?></div><div class="mini-stat-lbl">Pending Payments</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-notes-medical"></i></div><div><div class="mini-stat-val"><?= $total_records ?></div><div class="mini-stat-lbl">Medical Records</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-star"></i></div><div><div class="mini-stat-val"><?= $avg_rating ?? '—' ?></div><div class="mini-stat-lbl">Avg Patient Rating</div></div></div>
    </div>

    <div class="reports-grid">
      <?php foreach ($reports as $r):
        $c = $color_map[$r['color']];
      ?>
      <a href="<?= $r['href'] ?>" class="report-card">
        <div class="report-header">
          <div class="report-icon" style="background:<?=$c['bg']?>;color:<?=$c['color']?>">
            <i class="<?=$r['icon']?>"></i>
          </div>
          <span class="report-tag" style="background:<?=$c['bg']?>;color:<?=$c['color']?>"><?= $r['tag'] ?></span>
        </div>
        <div class="report-title"><?= $r['title'] ?></div>
        <div class="report-desc"><?= $r['desc'] ?></div>
        <div class="report-footer">
          <span class="report-cta" style="color:<?=$c['color']?>">
            View Report <i class="fas fa-arrow-right report-arrow"></i>
          </span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

  </main>
</div>
</body>
</html>