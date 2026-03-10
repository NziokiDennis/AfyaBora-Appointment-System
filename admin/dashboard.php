<?php
session_set_cookie_params(["path" => "/Appointment_system/admin", "domain" => $_SERVER['HTTP_HOST'], "httponly" => true, "secure" => false]);
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}
$admin_name = $_SESSION["full_name"] ?? "Administrator";
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);
mysqli_report(MYSQLI_REPORT_OFF);
// Safe query — never crashes on missing table
function safeCount($conn, $sql) {
    $r = @$conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return (int)($row['c'] ?? $row['total'] ?? 0);
}
function safeVal($conn, $sql, $col = 't') {
    $r = @$conn->query($sql);
    if (!$r) return '0';
    $row = $r->fetch_assoc();
    return $row[$col] ?? '0';
}

$users        = safeCount($conn, "SELECT COUNT(*) AS c FROM users");
$patients     = safeCount($conn, "SELECT COUNT(*) AS c FROM users WHERE role='patient'");
$doctors      = safeCount($conn, "SELECT COUNT(*) AS c FROM users WHERE role='doctor'");
$appointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments");
$scheduled    = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'");
$completed    = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='completed'");
$canceled     = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='canceled'");
$medRecords   = safeCount($conn, "SELECT COUNT(*) AS c FROM medical_records");
$revenue      = safeVal($conn,   "SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE payment_status='paid'");
$notif_count  = safeCount($conn, "SELECT COUNT(*) AS c FROM notifications WHERE is_read=0");

$recentApp = @$conn->query("
    SELECT a.appointment_date, a.appointment_time,
           u.full_name AS patient, d.full_name AS doctor, a.status
    FROM appointments a
    JOIN patients  p ON a.patient_id = p.patient_id
    JOIN users     u ON p.user_id    = u.user_id
    JOIN users     d ON a.doctor_id  = d.user_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");

$chartLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$chartData   = array_fill(0, 12, 0);
$cr = @$conn->query("SELECT MONTH(appointment_date) AS m, COUNT(*) AS cnt FROM appointments WHERE YEAR(appointment_date)=YEAR(CURDATE()) GROUP BY m ORDER BY m");
if ($cr) while ($row = $cr->fetch_assoc()) $chartData[(int)$row['m']-1] = (int)$row['cnt'];

$docLabels = []; $docData = [];
$dr = @$conn->query("SELECT u.full_name, COUNT(a.appointment_id) AS cnt FROM users u LEFT JOIN appointments a ON u.user_id=a.doctor_id WHERE u.role='doctor' GROUP BY u.user_id,u.full_name ORDER BY cnt DESC LIMIT 5");
if ($dr) while ($row = $dr->fetch_assoc()) { $docLabels[] = $row['full_name']; $docData[] = (int)$row['cnt']; }

$tot  = max($appointments, 1);
$sPct = round($scheduled / $tot * 100);
$cPct = round($completed  / $tot * 100);
$xPct = round($canceled   / $tot * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — HealthAdmin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
  --bg:#0f1117; --surface:#181c27; --surface2:#1e2335; --border:rgba(255,255,255,.06);
  --teal:#0ec4a4; --teal2:#08a688; --teal-glow:rgba(14,196,164,.15);
  --blue:#3b7cff; --amber:#f5a623; --rose:#f05b70; --green:#34c97d;
  --text:#e8eaf2; --muted:#6b7280;
  --sidebar-w:230px; --header-h:60px; --radius:13px;
  --font:'Plus Jakarta Sans',sans-serif; --font-mono:'Space Grotesk',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{font-family:var(--font);background:var(--bg);color:var(--text);display:flex;overflow:hidden}
::-webkit-scrollbar{width:4px}::-webkit-scrollbar-thumb{background:var(--surface2);border-radius:99px}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;left:0;top:0;bottom:0;z-index:100;overflow-y:auto}
.sb-brand{height:var(--header-h);display:flex;align-items:center;gap:10px;padding:0 18px;border-bottom:1px solid var(--border);flex-shrink:0}
.sb-brand .li{width:32px;height:32px;background:linear-gradient(135deg,var(--teal),var(--teal2));border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem;color:#fff;box-shadow:0 0 14px var(--teal-glow);flex-shrink:0}
.sb-brand .bt{font-weight:700;font-size:.95rem;letter-spacing:-.2px}
.sb-brand .bt span{color:var(--teal)}
.sb-user{padding:14px 18px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border);flex-shrink:0}
.sb-av{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--blue));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:#fff;flex-shrink:0}
.sb-un{font-weight:600;font-size:.82rem;line-height:1.2}
.sb-ur{font-size:.68rem;color:var(--teal);text-transform:uppercase;letter-spacing:.05em}
.sb-nav{padding:14px 10px;flex:1}
.sb-lbl{font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);padding:0 10px;margin:14px 0 5px}
.sb-lbl:first-child{margin-top:0}
.nav-item{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:9px;color:var(--muted);font-size:.82rem;font-weight:500;text-decoration:none;transition:all .18s;position:relative;margin-bottom:1px}
.nav-item:hover{background:var(--surface2);color:var(--text)}
.nav-item.active{background:var(--teal-glow);color:var(--teal);font-weight:600}
.nav-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:55%;background:var(--teal);border-radius:0 3px 3px 0}
.nav-item i{width:16px;text-align:center;font-size:.82rem;flex-shrink:0}
.nav-badge{margin-left:auto;background:var(--rose);color:#fff;font-size:.62rem;font-weight:700;padding:2px 6px;border-radius:99px}
.sb-footer{padding:10px;border-top:1px solid var(--border);flex-shrink:0}
.sb-footer a{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:9px;color:var(--rose);font-size:.82rem;font-weight:500;text-decoration:none;transition:background .18s}
.sb-footer a:hover{background:rgba(240,91,112,.1)}

/* MAIN */
.main-wrap{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;overflow:hidden}
.topbar{height:var(--header-h);background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 24px;gap:12px;flex-shrink:0;position:sticky;top:0;z-index:50}
.topbar-title{font-size:1rem;font-weight:700}
.topbar-crumb{font-size:.73rem;color:var(--muted);display:flex;align-items:center;gap:5px}
.topbar-crumb a{color:var(--teal);text-decoration:none}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:8px}
.topbar-chip{font-size:.73rem;color:var(--muted);background:var(--surface2);border:1px solid var(--border);padding:5px 11px;border-radius:7px;display:flex;align-items:center;gap:6px}
.topbar-icon-btn{width:33px;height:33px;border-radius:8px;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:.8rem;text-decoration:none;transition:all .18s;position:relative;cursor:pointer}
.topbar-icon-btn:hover{background:var(--teal-glow);color:var(--teal);border-color:var(--teal)}
.notif-dot{position:absolute;top:5px;right:5px;width:7px;height:7px;border-radius:50%;background:var(--rose);border:2px solid var(--surface)}
.page-content{flex:1;overflow-y:auto;padding:22px}

/* CARDS */
.ha-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:0}

/* TABLES */
.ha-table{width:100%;border-collapse:collapse}
.ha-table thead th{padding:9px 13px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);border-bottom:1px solid var(--border);text-align:left}
.ha-table tbody td{padding:11px 13px;font-size:.81rem;border-bottom:1px solid var(--border);vertical-align:middle}
.ha-table tbody tr:last-child td{border-bottom:none}
.ha-table tbody tr:hover td{background:var(--surface2)}

/* BADGES */
.ha-badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:99px;font-size:.69rem;font-weight:700;text-transform:capitalize}
.badge-scheduled{background:rgba(59,124,255,.15);color:var(--blue)}
.badge-completed{background:rgba(52,201,125,.15);color:var(--green)}
.badge-canceled{background:rgba(240,91,112,.15);color:var(--rose)}
.badge-rescheduled{background:rgba(245,166,35,.15);color:var(--amber)}
.badge-no_show{background:rgba(107,114,128,.15);color:var(--muted)}

/* DASHBOARD LAYOUT */
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;display:flex;align-items:flex-start;gap:12px;transition:transform .2s,border-color .2s}
.stat-card:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.1)}
.stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}
.si-teal{background:var(--teal-glow);color:var(--teal)}
.si-blue{background:rgba(59,124,255,.15);color:var(--blue)}
.si-amber{background:rgba(245,166,35,.15);color:var(--amber)}
.si-rose{background:rgba(240,91,112,.15);color:var(--rose)}
.si-green{background:rgba(52,201,125,.15);color:var(--green)}
.stat-val{font-family:var(--font-mono);font-size:1.55rem;font-weight:700;line-height:1;margin-bottom:3px}
.stat-lbl{font-size:.75rem;color:var(--muted)}
.stat-sub{font-size:.69rem;color:var(--teal);margin-top:5px}

.status-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
.sc{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:15px 18px;display:flex;align-items:center;gap:11px}
.sc-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.sc-num{font-family:var(--font-mono);font-size:1.3rem;font-weight:700;line-height:1}
.sc-lbl{font-size:.72rem;color:var(--muted);margin-top:2px}

.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
.bottom-row{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:18px}

.qa{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:9px;background:var(--surface2);border:1px solid var(--border);color:var(--text);text-decoration:none;font-size:.8rem;font-weight:500;transition:all .18s;margin-bottom:7px}
.qa:last-child{margin-bottom:0}
.qa:hover{border-color:var(--teal);color:var(--teal);background:var(--teal-glow)}
.qa i:first-child{color:var(--teal);width:16px;text-align:center}
.qa-arr{margin-left:auto;color:var(--muted);font-size:.68rem}

.p-row{display:flex;align-items:center;gap:10px;margin-bottom:11px}
.p-lbl{font-size:.74rem;color:var(--muted);min-width:80px}
.p-track{flex:1;height:5px;background:var(--surface2);border-radius:99px;overflow:hidden}
.p-fill{height:100%;border-radius:99px}
.p-pct{font-size:.7rem;font-weight:700;min-width:34px;text-align:right}

.alert-banner{background:rgba(14,196,164,.07);border:1px solid rgba(14,196,164,.18);border-radius:10px;padding:11px 16px;display:flex;align-items:center;gap:10px;margin-bottom:18px;font-size:.8rem}
.alert-banner i{color:var(--teal)}

@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.stat-card{animation:fadeUp .4s ease both}

@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:850px){.charts-row,.bottom-row,.status-strip{grid-template-columns:1fr}}
@media(max-width:640px){.stats-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-brand">
    <div class="li"><i class="fas fa-heartbeat"></i></div>
    <div class="bt">Afya<span>Bora</span></div>
  </div>
  <div class="sb-user">
    <div class="sb-av"><?= strtoupper(substr($admin_name,0,2)) ?></div>
    <div>
      <div class="sb-un"><?= htmlspecialchars($admin_name) ?></div>
      <div class="sb-ur">Administrator</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-lbl">Main</div>
    <a class="nav-item active" href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
    <a class="nav-item" href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments <?php if($scheduled>0):?><span class="nav-badge"><?=$scheduled?></span><?php endif;?></a>
    <a class="nav-item" href="patients.php"><i class="fas fa-user-injured"></i> Patients</a>
    <a class="nav-item" href="doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
    <div class="sb-lbl">Management</div>
    <a class="nav-item" href="users.php"><i class="fas fa-users-cog"></i> User Management</a>
    <div class="sb-lbl">Analytics</div>
    <a class="nav-item" href="reports/reports_dashboard.php"><i class="fas fa-chart-bar"></i> Reports</a>
    <a class="nav-item" href="notifications.php"><i class="fas fa-bell"></i> Notifications <?php if($notif_count>0):?><span class="nav-badge"><?=$notif_count?></span><?php endif;?></a>
    <a class="nav-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
  </nav>
  <div class="sb-footer">
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Dashboard</div>
      <div class="topbar-crumb"><a href="dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.5rem"></i> <span>Dashboard</span></div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i> <?= date("D, M j Y") ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="settings.php" class="topbar-icon-btn"><i class="fas fa-cog"></i></a>
    </div>
  </header>

  <main class="page-content">

    <div class="alert-banner">
      <i class="fas fa-circle-check"></i>
      System operational — all services running normally. Last sync: <strong><?= date("H:i") ?></strong>
      <?php if($notif_count>0):?> &nbsp;·&nbsp; <a href="notifications.php" style="color:var(--teal);font-weight:600;text-decoration:none"><?=$notif_count?> unread notification<?=$notif_count>1?'s':''?></a><?php endif;?>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card" style="animation-delay:.05s"><div class="stat-icon si-teal"><i class="fas fa-user-injured"></i></div><div><div class="stat-val"><?=$patients?></div><div class="stat-lbl">Active Patients</div><div class="stat-sub"><i class="fas fa-arrow-up"></i> Registered</div></div></div>
      <div class="stat-card" style="animation-delay:.10s"><div class="stat-icon si-blue"><i class="fas fa-user-md"></i></div><div><div class="stat-val"><?=$doctors?></div><div class="stat-lbl">Doctors</div><div class="stat-sub"><i class="fas fa-stethoscope"></i> Medical staff</div></div></div>
      <div class="stat-card" style="animation-delay:.15s"><div class="stat-icon si-amber"><i class="fas fa-calendar-alt"></i></div><div><div class="stat-val"><?=$appointments?></div><div class="stat-lbl">Total Appointments</div><div class="stat-sub"><i class="fas fa-clock"></i> <?=$scheduled?> scheduled</div></div></div>
      <div class="stat-card" style="animation-delay:.20s"><div class="stat-icon si-green"><i class="fas fa-money-bill-wave"></i></div><div><div class="stat-val" style="font-size:1.1rem">KES <?=number_format((float)$revenue,0)?></div><div class="stat-lbl">Revenue Collected</div><div class="stat-sub"><i class="fas fa-receipt"></i> Paid only</div></div></div>
      <div class="stat-card" style="animation-delay:.25s"><div class="stat-icon si-rose"><i class="fas fa-users"></i></div><div><div class="stat-val"><?=$users?></div><div class="stat-lbl">Total Users</div><div class="stat-sub"><i class="fas fa-layer-group"></i> All roles</div></div></div>
      <div class="stat-card" style="animation-delay:.30s"><div class="stat-icon si-teal"><i class="fas fa-notes-medical"></i></div><div><div class="stat-val"><?=$medRecords?></div><div class="stat-lbl">Medical Records</div><div class="stat-sub"><i class="fas fa-file-medical"></i> Clinical data</div></div></div>
    </div>

    <!-- Status strip -->
    <div class="status-strip">
      <div class="sc"><div class="sc-dot" style="background:var(--blue)"></div><div><div class="sc-num" style="color:var(--blue)"><?=$scheduled?></div><div class="sc-lbl">Scheduled</div></div><i class="fas fa-calendar-check" style="color:var(--blue);opacity:.25;font-size:1.4rem;margin-left:auto"></i></div>
      <div class="sc"><div class="sc-dot" style="background:var(--green)"></div><div><div class="sc-num" style="color:var(--green)"><?=$completed?></div><div class="sc-lbl">Completed</div></div><i class="fas fa-circle-check" style="color:var(--green);opacity:.25;font-size:1.4rem;margin-left:auto"></i></div>
      <div class="sc"><div class="sc-dot" style="background:var(--rose)"></div><div><div class="sc-num" style="color:var(--rose)"><?=$canceled?></div><div class="sc-lbl">Canceled</div></div><i class="fas fa-circle-xmark" style="color:var(--rose);opacity:.25;font-size:1.4rem;margin-left:auto"></i></div>
    </div>

    <!-- Charts -->
    <div class="charts-row">
      <div class="ha-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
          <span style="font-size:.82rem;font-weight:700;display:flex;align-items:center;gap:7px"><i class="fas fa-chart-line" style="color:var(--teal)"></i> Monthly Appointments</span>
          <span style="font-size:.7rem;color:var(--muted)"><?=date('Y')?></span>
        </div>
        <canvas id="lineChart" style="max-height:200px"></canvas>
      </div>
      <div class="ha-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
          <span style="font-size:.82rem;font-weight:700;display:flex;align-items:center;gap:7px"><i class="fas fa-chart-bar" style="color:var(--teal)"></i> Doctor Workload</span>
          <span style="font-size:.7rem;color:var(--muted)">Top 5</span>
        </div>
        <canvas id="barChart" style="max-height:200px"></canvas>
      </div>
    </div>

    <!-- Bottom -->
    <div class="bottom-row">
      <div class="ha-card" style="padding:0;overflow:hidden">
        <div style="padding:15px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:.82rem;font-weight:700;display:flex;align-items:center;gap:7px"><i class="fas fa-clock-rotate-left" style="color:var(--teal)"></i> Recent Appointments</span>
          <a href="appointments.php" style="font-size:.75rem;color:var(--teal);text-decoration:none;font-weight:600">View all <i class="fas fa-arrow-right" style="font-size:.58rem"></i></a>
        </div>
        <?php if($recentApp && $recentApp->num_rows>0): ?>
        <table class="ha-table">
          <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
          <tbody>
            <?php while($r=$recentApp->fetch_assoc()): ?>
            <tr>
              <td><?=htmlspecialchars($r['appointment_date'])?></td>
              <td><?=htmlspecialchars($r['appointment_time'])?></td>
              <td style="font-weight:500"><?=htmlspecialchars($r['patient'])?></td>
              <td style="color:var(--teal)"><?=htmlspecialchars($r['doctor'])?></td>
              <td><span class="ha-badge badge-<?=strtolower($r['status'])?>"><?=ucfirst($r['status'])?></span></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div style="padding:32px;text-align:center;color:var(--muted);font-size:.82rem"><i class="fas fa-calendar-xmark" style="font-size:1.4rem;opacity:.25;display:block;margin-bottom:8px"></i>No appointments yet.</div>
        <?php endif; ?>
      </div>

      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="ha-card">
          <div style="font-size:.73rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:13px"><i class="fas fa-bolt" style="color:var(--teal);margin-right:5px"></i>Quick Actions</div>
          <a href="appointments.php"              class="qa"><i class="fas fa-calendar-check"></i> Appointments    <i class="fas fa-chevron-right qa-arr"></i></a>
          <a href="patients.php"                  class="qa"><i class="fas fa-user-injured"></i>   Patients         <i class="fas fa-chevron-right qa-arr"></i></a>
          <a href="doctors.php"                   class="qa"><i class="fas fa-user-md"></i>        Doctors          <i class="fas fa-chevron-right qa-arr"></i></a>
          <a href="users.php"                     class="qa"><i class="fas fa-users-cog"></i>      User Management  <i class="fas fa-chevron-right qa-arr"></i></a>
          <a href="reports/reports_dashboard.php" class="qa"><i class="fas fa-chart-bar"></i>      Reports          <i class="fas fa-chevron-right qa-arr"></i></a>
        </div>
        <div class="ha-card">
          <div style="font-size:.73rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:13px"><i class="fas fa-chart-simple" style="color:var(--teal);margin-right:5px"></i>Appointment Breakdown</div>
          <div class="p-row"><span class="p-lbl">Scheduled</span><div class="p-track"><div class="p-fill" style="width:<?=$sPct?>%;background:var(--blue)"></div></div><span class="p-pct" style="color:var(--blue)"><?=$sPct?>%</span></div>
          <div class="p-row"><span class="p-lbl">Completed</span><div class="p-track"><div class="p-fill" style="width:<?=$cPct?>%;background:var(--green)"></div></div><span class="p-pct" style="color:var(--green)"><?=$cPct?>%</span></div>
          <div class="p-row"><span class="p-lbl">Canceled</span> <div class="p-track"><div class="p-fill" style="width:<?=$xPct?>%;background:var(--rose)"></div></div><span class="p-pct" style="color:var(--rose)"><?=$xPct?>%</span></div>
        </div>
      </div>
    </div>

  </main>
</div>

<script>
Chart.defaults.color='#6b7280';Chart.defaults.borderColor='rgba(255,255,255,0.06)';Chart.defaults.font.family='Plus Jakarta Sans';
const lc=document.getElementById('lineChart').getContext('2d');
const lg=lc.createLinearGradient(0,0,0,200);lg.addColorStop(0,'rgba(14,196,164,.3)');lg.addColorStop(1,'rgba(14,196,164,0)');
new Chart(lc,{type:'line',data:{labels:<?=json_encode($chartLabels)?>,datasets:[{data:<?=json_encode($chartData)?>,fill:true,backgroundColor:lg,borderColor:'#0ec4a4',borderWidth:2.5,tension:0.45,pointBackgroundColor:'#0ec4a4',pointRadius:3,pointHoverRadius:5}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{precision:0}}}}});
new Chart(document.getElementById('barChart'),{type:'bar',data:{labels:<?=json_encode($docLabels)?>,datasets:[{data:<?=json_encode($docData)?>,backgroundColor:'rgba(59,124,255,.7)',borderRadius:5,borderSkipped:false}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{precision:0}}}}});
</script>
</body>
</html>