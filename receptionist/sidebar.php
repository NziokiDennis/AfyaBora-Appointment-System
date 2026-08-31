<?php
// Expects $current_page to be set by the including page (e.g. 'dashboard', 'payments', 'pharmacy').
// Expects $conn (mysqli) and a logged-in receptionist session to already exist.

$__sidebar_pending = (int)($conn->query("
    SELECT COUNT(*) AS c FROM appointments
    WHERE status = 'scheduled' AND payment_status = 'pending'
      AND payment_reference IS NOT NULL AND payment_reference != ''
")->fetch_assoc()["c"] ?? 0);
?>
<style>
:root {
  --navy: #002d70;
  --navy2: #134589;
  --blue: #0b63c3;
  --blue2: #094f9e;
  --sky: #eef3fb;
  --canvas: #f5f6fa;
  --white: #ffffff;
  --border: #e8ebf0;
  --muted: #5b6169;
  --green: #1fae7a;
  --amber: #f59e0b;
  --rose: #dc2626;
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-pill: 999px;
  --sidebar-w: 258px;
}
* { box-sizing: border-box; }
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: var(--navy);
  background: var(--canvas);
  margin: 0;
}
a { text-decoration: none; }

.ab-sidebar {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--navy);
  display: flex;
  flex-direction: column;
  padding: 22px 16px;
  overflow-y: auto;
  z-index: 100;
}
.ab-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 8px 22px;
  color: #fff;
  font-weight: 700;
  font-size: 1.05rem;
}
.ab-brand .ab-brand-icon {
  width: 34px; height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--blue), var(--blue2));
  display: flex; align-items: center; justify-content: center;
  font-size: .88rem;
  flex-shrink: 0;
}
.ab-brand span { color: #93c5fd; }

.ab-nav { display: flex; flex-direction: column; gap: 3px; flex: 1; }
.ab-nav-item {
  display: flex; align-items: center; gap: 11px;
  padding: 10px 12px;
  border-radius: var(--radius-md);
  color: rgba(255,255,255,.68);
  font-size: .875rem;
  font-weight: 500;
  transition: background .15s, color .15s;
}
.ab-nav-item i { width: 18px; text-align: center; font-size: .92rem; }
.ab-nav-item:hover { background: var(--navy2); color: #fff; }
.ab-nav-item.active { background: var(--navy2); color: #fff; font-weight: 600; }
.ab-nav-item.logout { color: rgba(255,255,255,.5); margin-top: 6px; }
.ab-nav-item.logout:hover { color: #fff; background: rgba(220,38,38,.18); }

.ab-sidebar-widget {
  margin-top: 16px;
  background: var(--navy2);
  border-radius: var(--radius-lg);
  padding: 16px;
  color: #fff;
}
.ab-sidebar-widget .aw-title {
  font-size: .76rem;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: rgba(255,255,255,.55);
  margin-bottom: 8px;
}
.ab-sidebar-widget .aw-pct {
  font-size: 1.6rem;
  font-weight: 800;
  margin-bottom: 4px;
}
.ab-sidebar-widget .aw-note {
  font-size: .84rem;
  color: rgba(255,255,255,.7);
}

.ab-main-wrap { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }

.ab-topbar {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 16px 28px;
  display: flex; align-items: center; justify-content: space-between;
  gap: 16px;
}
.ab-topbar-left .ab-greeting { font-size: 1.05rem; font-weight: 700; color: var(--navy); }
.ab-topbar-left .ab-subgreeting { font-size: .85rem; color: var(--muted); margin-top: 2px; }
.ab-topbar-right { display: flex; align-items: center; gap: 12px; }

.ab-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 16px;
  border-radius: var(--radius-sm);
  font-size: .88rem; font-weight: 600;
  border: 1.5px solid transparent;
  cursor: pointer;
  transition: all .15s;
}
.ab-btn-primary { background: var(--navy); color: #fff; }
.ab-btn-primary:hover { background: var(--blue2); }
.ab-btn-secondary { background: #fff; color: var(--blue); border-color: var(--blue); }
.ab-btn-secondary:hover { background: var(--sky); }
.ab-btn-danger { background: #fff; color: var(--rose); border-color: var(--rose); }
.ab-btn-danger:hover { background: #fef2f2; }
.ab-btn-sm { padding: 6px 12px; font-size: .84rem; }

.ab-user-chip { display: flex; align-items: center; gap: 9px; }
.ab-user-avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--blue), var(--blue2));
  color: #fff; font-weight: 700; font-size: .78rem;
  display: flex; align-items: center; justify-content: center;
}
.ab-user-name { font-size: .88rem; font-weight: 600; color: var(--navy); }

.ab-content { padding: 24px 28px 40px; flex: 1; }

.ab-center-viewport { max-width: 620px; margin: 0 auto; }
@media (min-width: 901px) {
  .ab-center-viewport { margin-left: calc(50vw - var(--sidebar-w) - 28px - 310px); margin-right: 0; }
}

.ab-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 22px;
}
.ab-card-title {
  font-size: 1.02rem; font-weight: 700; color: var(--navy);
  display: flex; align-items: center; gap: 9px;
  margin-bottom: 4px;
}
.ab-card-title i { color: var(--blue); font-size: .95rem; }

.ab-icon-chip {
  width: 38px; height: 38px; border-radius: var(--radius-md);
  background: var(--sky); color: var(--blue);
  display: flex; align-items: center; justify-content: center;
  font-size: .95rem; flex-shrink: 0;
}

.ab-pill {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: var(--radius-pill);
  font-size: .74rem; font-weight: 700;
}
.ab-pill-green { background: rgba(22,163,74,.12); color: var(--green); }
.ab-pill-amber { background: rgba(245,158,11,.14); color: #b45309; }
.ab-pill-rose  { background: rgba(220,38,38,.12); color: var(--rose); }
.ab-pill-blue  { background: rgba(11,99,195,.12); color: var(--blue); }
.ab-pill-neutral { background: transparent; border: 1px solid var(--border); color: var(--muted); font-weight: 600; }

.ab-page-title { font-size: 1.7rem; font-weight: 800; color: var(--navy); margin-bottom: 4px; }
.ab-page-sub { color: var(--muted); font-size: .92rem; margin-bottom: 22px; }

.ab-list-row { border-bottom: 1px solid var(--border); padding: 14px 0; display: flex; align-items: flex-start; gap: 12px; }
.ab-list-row:last-child { border-bottom: none; padding-bottom: 4px; }
.ab-list-row .alr-body { flex: 1; min-width: 0; }
.ab-list-row .alr-title-line { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ab-list-row .alr-title { font-weight: 600; color: var(--navy); font-size: .92rem; }
.ab-list-row .alr-meta { color: var(--muted); font-size: .82rem; margin-top: 3px; }
.ab-list-row .alr-detail { font-size: .85rem; color: var(--navy); margin-top: 8px; }
.ab-list-row .alr-detail-label { color: var(--muted); font-weight: 600; }
.ab-list-row .alr-trailing { flex-shrink: 0; display: flex; align-items: center; gap: 6px; }
.ab-list-row .alr-view { color: var(--border); font-size: .85rem; transition: color .15s; }
.ab-list-row .alr-view:hover { color: var(--blue); }

.ab-alert { padding: 11px 15px; border-radius: var(--radius-sm); font-size: .88rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.ab-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: var(--green); }
.ab-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: var(--rose); }
.ab-alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; }

.ab-form-group { margin-bottom: 18px; }
.ab-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 640px) { .ab-form-row { grid-template-columns: 1fr; } }
.ab-label { display: block; font-size: .84rem; font-weight: 600; color: var(--navy); margin-bottom: 7px; }
.ab-label .req { color: var(--rose); }
.ab-input, select.ab-input, textarea.ab-input {
  width: 100%; padding: 10px 13px;
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  background: var(--white); color: var(--navy);
  font-family: 'Plus Jakarta Sans', sans-serif; font-size: .92rem;
  outline: none; transition: border-color .15s, box-shadow .15s;
}
.ab-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(11,99,195,.12); }
.ab-input::placeholder { color: #9aa1a8; }
textarea.ab-input { resize: vertical; min-height: 90px; }
.ab-field-hint { font-size: .82rem; color: var(--muted); margin-top: 6px; }
.ab-field-error { font-size: .84rem; color: var(--rose); margin-top: 6px; display: flex; align-items: center; gap: 6px; }
.ab-field-error[hidden] { display: none; }
.ab-info-banner {
  background: var(--sky); border: 1px solid var(--border); border-radius: var(--radius-md);
  padding: 12px 14px; margin-bottom: 18px; font-size: .86rem; color: var(--navy); line-height: 1.5;
}
.ab-info-banner strong { color: var(--navy); }

.ab-stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
@media (max-width: 700px) { .ab-stat-row { grid-template-columns: 1fr; } }
.ab-stat-tile { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px 20px; display: flex; align-items: center; gap: 14px; }
.ab-stat-tile .ab-icon-chip { width: 44px; height: 44px; font-size: 1.05rem; }
.ab-stat-tile .ab-stat-num { font-size: 1.5rem; font-weight: 800; color: var(--navy); line-height: 1.1; }
.ab-stat-tile .ab-stat-label { font-size: .84rem; color: var(--muted); margin-top: 2px; }

@media (max-width: 900px) {
  .ab-sidebar { display: none; }
  .ab-main-wrap { margin-left: 0; }
}
</style>

<aside class="ab-sidebar">
  <div class="ab-brand">
    <div class="ab-brand-icon"><i class="fas fa-heartbeat"></i></div>
    Afya<span>Bora</span>
  </div>
  <nav class="ab-nav">
    <a href="dashboard.php" class="ab-nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-grip"></i> Dashboard</a>
    <a href="payments.php" class="ab-nav-item <?= $current_page === 'payments' ? 'active' : '' ?>"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="pharmacy.php" class="ab-nav-item <?= $current_page === 'pharmacy' ? 'active' : '' ?>"><i class="fas fa-pills"></i> Pharmacy</a>
    <a href="../logout.php" class="ab-nav-item logout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </nav>

  <div class="ab-sidebar-widget">
    <div class="aw-title">Pending Payments</div>
    <div class="aw-pct"><?= $__sidebar_pending ?></div>
    <div class="aw-note"><?= $__sidebar_pending === 1 ? 'submission awaiting confirmation' : 'submissions awaiting confirmation' ?></div>
  </div>
</aside>
