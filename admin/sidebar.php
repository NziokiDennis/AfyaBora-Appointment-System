<?php
// sidebar.php — included by every admin page
// Expects: $current_page (string matching a nav key), $admin_name (string)
// Requires: db connection already open for notification count

$current = $current_page ?? '';

// Notification count
$notif_count = 0;
if (isset($conn)) {
    $nr = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read = 0");
    if ($nr) $notif_count = (int)$nr->fetch_assoc()['c'];
}
?>
<style>
/* ── Design tokens ─────────────────────────────── */
:root {
  --bg:        #0f1117;
  --surface:   #181c27;
  --surface2:  #1e2335;
  --surface3:  #252a3a;
  --border:    rgba(255,255,255,0.06);
  --teal:      #0ec4a4;
  --teal2:     #08a688;
  --teal-glow: rgba(14,196,164,0.15);
  --blue:      #3b7cff;
  --amber:     #f5a623;
  --rose:      #f05b70;
  --green:     #34c97d;
  --text:      #e8eaf2;
  --muted:     #6b7280;
  --sidebar-w: 230px;
  --header-h:  60px;
  --radius:    13px;
  --font:      'Plus Jakarta Sans', sans-serif;
  --font-mono: 'Space Grotesk', monospace;
}

/* ── Base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
  font-family: var(--font);
  background: var(--bg);
  color: var(--text);
  display: flex;
  overflow: hidden;
}
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-thumb { background: var(--surface3); border-radius: 99px; }

/* ── Sidebar ── */
.sidebar {
  width: var(--sidebar-w);
  min-height: 100vh;
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0; top: 0; bottom: 0;
  z-index: 100;
  overflow-y: auto;
}
.sb-brand {
  height: var(--header-h);
  display: flex; align-items: center; gap: 10px;
  padding: 0 18px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.sb-brand .logo-icon {
  width: 32px; height: 32px;
  background: linear-gradient(135deg, var(--teal), var(--teal2));
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem; color: #fff;
  box-shadow: 0 0 14px var(--teal-glow);
  flex-shrink: 0;
}
.sb-brand .brand-text { font-weight: 700; font-size: .95rem; letter-spacing: -.2px; }
.sb-brand .brand-text span { color: var(--teal); }

.sb-user {
  padding: 14px 18px;
  display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.sb-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, var(--teal), var(--blue));
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: .8rem; color: #fff; flex-shrink: 0;
}
.sb-user-name  { font-weight: 600; font-size: .82rem; line-height: 1.2; }
.sb-user-role  { font-size: .68rem; color: var(--teal); text-transform: uppercase; letter-spacing: .05em; }

.sb-nav { padding: 14px 10px; flex: 1; }
.sb-section-label {
  font-size: .62rem; font-weight: 700;
  letter-spacing: .12em; text-transform: uppercase;
  color: var(--muted);
  padding: 0 10px; margin: 14px 0 5px;
}
.sb-section-label:first-child { margin-top: 0; }

.nav-item {
  display: flex; align-items: center; gap: 9px;
  padding: 9px 10px;
  border-radius: 9px;
  color: var(--muted);
  font-size: .82rem; font-weight: 500;
  text-decoration: none;
  transition: all .18s;
  position: relative;
  margin-bottom: 1px;
}
.nav-item:hover { background: var(--surface2); color: var(--text); }
.nav-item.active { background: var(--teal-glow); color: var(--teal); font-weight: 600; }
.nav-item.active::before {
  content: '';
  position: absolute; left: 0; top: 50%; transform: translateY(-50%);
  width: 3px; height: 55%;
  background: var(--teal); border-radius: 0 3px 3px 0;
}
.nav-item i { width: 16px; text-align: center; font-size: .82rem; flex-shrink: 0; }
.nav-badge {
  margin-left: auto;
  background: var(--rose); color: #fff;
  font-size: .62rem; font-weight: 700;
  padding: 2px 6px; border-radius: 99px;
}
.nav-badge.amber { background: var(--amber); }

.sb-footer {
  padding: 10px;
  border-top: 1px solid var(--border);
  flex-shrink: 0;
}
.sb-footer a {
  display: flex; align-items: center; gap: 9px;
  padding: 9px 10px; border-radius: 9px;
  color: var(--rose); font-size: .82rem; font-weight: 500;
  text-decoration: none; transition: background .18s;
}
.sb-footer a:hover { background: rgba(240,91,112,.1); }

/* ── Main wrapper ── */
.main-wrap {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex; flex-direction: column;
  min-height: 100vh;
  overflow: hidden;
}

/* ── Topbar ── */
.topbar {
  height: var(--header-h);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center;
  padding: 0 24px; gap: 12px;
  flex-shrink: 0;
  position: sticky; top: 0; z-index: 50;
}
.topbar-title  { font-size: 1rem; font-weight: 700; }
.topbar-crumb  { font-size: .73rem; color: var(--muted); display: flex; align-items: center; gap: 5px; }
.topbar-crumb a { color: var(--teal); text-decoration: none; }
.topbar-right  { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.topbar-chip {
  font-size: .73rem; color: var(--muted);
  background: var(--surface2); border: 1px solid var(--border);
  padding: 5px 11px; border-radius: 7px;
  display: flex; align-items: center; gap: 6px;
}
.topbar-icon-btn {
  width: 33px; height: 33px; border-radius: 8px;
  background: var(--surface2); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--muted); font-size: .8rem;
  text-decoration: none; transition: all .18s; position: relative;
  cursor: pointer;
}
.topbar-icon-btn:hover { background: var(--teal-glow); color: var(--teal); border-color: var(--teal); }
.notif-dot {
  position: absolute; top: 5px; right: 5px;
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--rose); border: 2px solid var(--surface);
}

/* ── Page content ── */
.page-content { flex: 1; overflow-y: auto; padding: 24px; }

/* ── Cards ── */
.ha-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px;
  margin-bottom: 20px;
}

/* ── Tables ── */
.ha-table { width: 100%; border-collapse: collapse; }
.ha-table thead th {
  padding: 9px 13px;
  font-size: .68rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .08em;
  color: var(--muted); border-bottom: 1px solid var(--border); text-align: left;
}
.ha-table tbody td {
  padding: 11px 13px; font-size: .81rem;
  border-bottom: 1px solid var(--border); vertical-align: middle;
}
.ha-table tbody tr:last-child td { border-bottom: none; }
.ha-table tbody tr:hover td { background: var(--surface2); }

/* ── Badges ── */
.ha-badge {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 3px 9px; border-radius: 99px;
  font-size: .69rem; font-weight: 700; text-transform: capitalize;
}
.badge-scheduled   { background: rgba(59,124,255,.15);  color: var(--blue);  }
.badge-completed   { background: rgba(52,201,125,.15);  color: var(--green); }
.badge-canceled    { background: rgba(240,91,112,.15);  color: var(--rose);  }
.badge-rescheduled { background: rgba(245,166,35,.15);  color: var(--amber); }
.badge-no_show     { background: rgba(107,114,128,.15); color: var(--muted); }
.badge-paid        { background: rgba(52,201,125,.15);  color: var(--green); }
.badge-pending     { background: rgba(245,166,35,.15);  color: var(--amber); }
.badge-unpaid      { background: rgba(240,91,112,.15);  color: var(--rose);  }
.badge-admin       { background: var(--teal-glow);      color: var(--teal);  }
.badge-doctor      { background: rgba(59,124,255,.15);  color: var(--blue);  }
.badge-patient     { background: rgba(52,201,125,.15);  color: var(--green); }

/* ── Buttons ── */
.ha-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px; border-radius: 8px;
  font-family: var(--font); font-size: .8rem; font-weight: 600;
  cursor: pointer; border: none; text-decoration: none; transition: all .18s;
}
.ha-btn-primary { background: var(--teal); color: #fff; }
.ha-btn-primary:hover { background: var(--teal2); }
.ha-btn-danger  { background: rgba(240,91,112,.15); color: var(--rose); border: 1px solid rgba(240,91,112,.3); }
.ha-btn-danger:hover { background: var(--rose); color: #fff; }
.ha-btn-ghost   { background: var(--surface2); border: 1px solid var(--border); color: var(--text); }
.ha-btn-ghost:hover { border-color: var(--teal); color: var(--teal); }
.ha-btn-sm { padding: 5px 10px; font-size: .73rem; }

/* ── Forms ── */
.ha-form-group { margin-bottom: 16px; }
.ha-label {
  display: block; font-size: .72rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .07em;
  color: var(--muted); margin-bottom: 6px;
}
.ha-input, .ha-select, .ha-textarea {
  width: 100%;
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 9px; padding: 10px 13px;
  color: var(--text); font-family: var(--font); font-size: .85rem;
  outline: none; transition: border-color .18s, box-shadow .18s;
}
.ha-input::placeholder, .ha-textarea::placeholder { color: var(--muted); }
.ha-input:focus, .ha-select:focus, .ha-textarea:focus {
  border-color: var(--teal); box-shadow: 0 0 0 3px var(--teal-glow);
}
.ha-select option { background: var(--surface2); }
.ha-textarea { resize: vertical; min-height: 90px; }

/* ── Alerts ── */
.ha-alert {
  padding: 11px 15px; border-radius: 9px; font-size: .81rem;
  display: flex; align-items: center; gap: 9px; margin-bottom: 16px;
}
.ha-alert-success { background: rgba(52,201,125,.1); border: 1px solid rgba(52,201,125,.25); color: var(--green); }
.ha-alert-danger  { background: rgba(240,91,112,.1); border: 1px solid rgba(240,91,112,.25); color: var(--rose);  }
.ha-alert-info    { background: rgba(14,196,164,.1); border: 1px solid rgba(14,196,164,.25); color: var(--teal);  }

/* ── Page header ── */
.page-header { margin-bottom: 22px; }
.page-header h2 {
  font-size: 1.25rem; font-weight: 700;
  display: flex; align-items: center; gap: 9px;
}
.page-header h2 i { color: var(--teal); }
.page-header p { color: var(--muted); font-size: .8rem; margin-top: 3px; }

/* ── Stat mini cards ── */
.mini-stats { display: flex; gap: 14px; margin-bottom: 20px; flex-wrap: wrap; }
.mini-stat {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 11px; padding: 14px 18px;
  display: flex; align-items: center; gap: 12px; flex: 1; min-width: 140px;
}
.mini-stat-icon { width: 38px; height: 38px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
.mini-stat-icon.teal  { background: var(--teal-glow); color: var(--teal); }
.mini-stat-icon.blue  { background: rgba(59,124,255,.15); color: var(--blue); }
.mini-stat-icon.amber { background: rgba(245,166,35,.15); color: var(--amber); }
.mini-stat-icon.rose  { background: rgba(240,91,112,.15); color: var(--rose); }
.mini-stat-icon.green { background: rgba(52,201,125,.15); color: var(--green); }
.mini-stat-val { font-family: var(--font-mono); font-size: 1.4rem; font-weight: 700; line-height: 1; }
.mini-stat-lbl { font-size: .72rem; color: var(--muted); margin-top: 2px; }

/* ── Search bar ── */
.search-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; }
.search-input-wrap { position: relative; flex: 1; max-width: 360px; }
.search-input-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: .8rem; pointer-events: none; }
.search-input-wrap input {
  width: 100%; background: var(--surface); border: 1px solid var(--border);
  border-radius: 9px; padding: 9px 13px 9px 35px;
  color: var(--text); font-family: var(--font); font-size: .82rem; outline: none;
  transition: border-color .18s;
}
.search-input-wrap input:focus { border-color: var(--teal); }
.search-input-wrap input::placeholder { color: var(--muted); }

/* ── Pagination ── */
.ha-pagination { display: flex; align-items: center; gap: 6px; margin-top: 18px; }
.ha-page-btn {
  width: 32px; height: 32px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: .78rem; font-weight: 600;
  background: var(--surface2); border: 1px solid var(--border);
  color: var(--muted); cursor: pointer; text-decoration: none; transition: all .18s;
}
.ha-page-btn:hover, .ha-page-btn.active { background: var(--teal-glow); color: var(--teal); border-color: var(--teal); }
.ha-page-info { font-size: .75rem; color: var(--muted); margin-left: auto; }

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}
.page-content > * { animation: fadeUp .3s ease both; }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-brand">
    <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
    <div class="brand-text">Health<span>Admin</span></div>
  </div>

  <div class="sb-user">
    <div class="sb-avatar"><?php echo strtoupper(substr($admin_name ?? 'A', 0, 2)); ?></div>
    <div>
      <div class="sb-user-name"><?php echo htmlspecialchars($admin_name ?? 'Admin'); ?></div>
      <div class="sb-user-role">Administrator</div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-section-label">Main</div>
    <a href="/Appointment_system/admin/dashboard.php"     class="nav-item <?= $current==='dashboard'    ?'active':'' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a>
    <a href="/Appointment_system/admin/appointments.php"  class="nav-item <?= $current==='appointments' ?'active':'' ?>">
      <i class="fas fa-calendar-check"></i> Appointments
      <?php if ($scheduled_count ?? 0): ?><span class="nav-badge"><?= $scheduled_count ?></span><?php endif; ?>
    </a>
    <a href="/Appointment_system/admin/patients.php"      class="nav-item <?= $current==='patients'     ?'active':'' ?>"><i class="fas fa-user-injured"></i> Patients</a>
    <a href="/Appointment_system/admin/doctors.php"       class="nav-item <?= $current==='doctors'      ?'active':'' ?>"><i class="fas fa-user-md"></i> Doctors</a>

    <div class="sb-section-label">Management</div>
    <a href="/Appointment_system/admin/users.php"         class="nav-item <?= $current==='users'        ?'active':'' ?>"><i class="fas fa-users-cog"></i> User Management</a>

    <div class="sb-section-label">Analytics</div>
    <a href="/Appointment_system/admin/reports/reports_dashboard.php" class="nav-item <?= $current==='reports' ?'active':'' ?>"><i class="fas fa-chart-bar"></i> Reports</a>
    <a href="/Appointment_system/admin/notifications.php" class="nav-item <?= $current==='notifications' ?'active':'' ?>">
      <i class="fas fa-bell"></i> Notifications
      <?php if ($notif_count > 0): ?><span class="nav-badge"><?= $notif_count ?></span><?php endif; ?>
    </a>
    <a href="/Appointment_system/admin/settings.php"      class="nav-item <?= $current==='settings'     ?'active':'' ?>"><i class="fas fa-cog"></i> Settings</a>
  </nav>

  <div class="sb-footer">
    <a href="/Appointment_system/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </div>
</aside>