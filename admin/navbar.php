<!-- Unified navbar is embedded in each page's sidebar; this file kept for legacy includes -->
<!-- If a page uses standalone navbar (non-sidebar layout), render a minimal topbar -->
<?php if (!defined('SIDEBAR_RENDERED')): ?>
<style>
:root {
  --bg:#0f1117;--surface:#181c27;--surface2:#1e2335;--border:rgba(255,255,255,.06);
  --teal:#0ec4a4;--teal-glow:rgba(14,196,164,.18);--blue:#3b7cff;--rose:#f05b70;
  --text:#e8eaf2;--muted:#6b7280;--font:'Plus Jakarta Sans',sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font);background:var(--bg);color:var(--text)}
.standalone-nav{
  height:60px;background:var(--surface);border-bottom:1px solid var(--border);
  display:flex;align-items:center;padding:0 24px;gap:16px;
}
.standalone-nav .brand{font-weight:700;font-size:1rem;color:var(--teal);text-decoration:none;display:flex;align-items:center;gap:8px}
.standalone-nav .nav-links{display:flex;align-items:center;gap:8px;margin-left:auto}
.standalone-nav a.nav-link-item{
  display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;
  color:var(--muted);font-size:.82rem;font-weight:500;text-decoration:none;transition:all .2s;
}
.standalone-nav a.nav-link-item:hover{background:var(--surface2);color:var(--text)}
.standalone-nav a.logout-btn{color:var(--rose)}
.standalone-nav a.logout-btn:hover{background:rgba(240,91,112,.1)}
</style>
<nav class="standalone-nav">
  <a href="/Appointment_system/admin/dashboard.php" class="brand">
    <i class="fas fa-heartbeat"></i> HealthAdmin
  </a>
  <div class="nav-links">
    <a href="/Appointment_system/admin/dashboard.php" class="nav-link-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
    <a href="/Appointment_system/admin/users.php" class="nav-link-item"><i class="fas fa-users-cog"></i> Users</a>
    <a href="/Appointment_system/admin/reports/reports_dashboard.php" class="nav-link-item"><i class="fas fa-chart-bar"></i> Reports</a>
    <a href="/Appointment_system/admin/logout.php" class="nav-link-item logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>
<?php endif; ?>