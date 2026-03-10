<?php
require_once "admin_auth.php";
require_once "../config/db.php";

$admin_name    = $_SESSION["full_name"] ?? "Admin";
$current_page  = 'appointments';

// Scheduled count for sidebar badge
$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

// Filters
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['search'] ?? '');

// Build query
$where_clauses = [];
$params        = [];
$types         = '';

if ($status_filter && $status_filter !== 'all') {
    $where_clauses[] = "a.status = ?";
    $params[]        = $status_filter;
    $types          .= 's';
}
if ($search !== '') {
    $where_clauses[] = "(u.full_name LIKE ? OR d.full_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$sql = "
    SELECT
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.reason,
        u.full_name  AS patient_name,
        d.full_name  AS doctor_name,
        COALESCE(p2.payment_status, 'unpaid') AS payment_status,
        p2.amount,
        p2.payment_date
    FROM appointments a
    JOIN patients  pt ON a.patient_id  = pt.patient_id
    JOIN users     u  ON pt.user_id    = u.user_id
    JOIN users     d  ON a.doctor_id   = d.user_id
    LEFT JOIN payments p2 ON a.appointment_id = p2.appointment_id
    $where_sql
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 50
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$rows   = $result->fetch_all(MYSQLI_ASSOC);

// Summary counts
$total_q    = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$sched_q    = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc()['c'];
$done_q     = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='completed'")->fetch_assoc()['c'];
$cancel_q   = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='canceled'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointments — HealthAdmin</title>
<?php include "sidebar.php"; ?>
</head>
<body>

<!-- SIDEBAR rendered via sidebar.php include above -->
<div class="main-wrap">

  <!-- Topbar -->
  <header class="topbar">
    <div>
      <div class="topbar-title">Appointments</div>
      <div class="topbar-crumb"><a href="dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Appointments</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="settings.php" class="topbar-icon-btn"><i class="fas fa-cog"></i></a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-calendar-check"></i> Appointments</h2>
      <p>Showing first 50 results. Use filters to narrow down.</p>
    </div>

    <!-- Mini stats -->
    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-calendar-alt"></i></div><div><div class="mini-stat-val"><?= $total_q ?></div><div class="mini-stat-lbl">Total</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-clock"></i></div><div><div class="mini-stat-val"><?= $sched_q ?></div><div class="mini-stat-lbl">Scheduled</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-circle-check"></i></div><div><div class="mini-stat-val"><?= $done_q ?></div><div class="mini-stat-lbl">Completed</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-circle-xmark"></i></div><div><div class="mini-stat-val"><?= $cancel_q ?></div><div class="mini-stat-lbl">Canceled</div></div></div>
    </div>

    <!-- Search + Filter -->
    <div class="search-wrap" style="flex-wrap:wrap;gap:10px;">
      <div class="search-input-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search patient or doctor..." value="<?= htmlspecialchars($search) ?>">
      </div>

      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php
        $statuses = ['all'=>'All', 'scheduled'=>'Scheduled', 'completed'=>'Completed', 'canceled'=>'Canceled', 'rescheduled'=>'Rescheduled', 'no_show'=>'No-Show'];
        foreach ($statuses as $val => $label):
          $active = ($status_filter === $val || ($val==='all' && !$status_filter)) ? 'active' : '';
        ?>
        <a href="?status=<?= $val ?><?= $search?"&search=".urlencode($search):'' ?>" class="ha-btn ha-btn-ghost ha-btn-sm <?= $active ?>" style="<?= $active?'border-color:var(--teal);color:var(--teal)':'' ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Table -->
    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="overflow-x:auto">
      <table class="ha-table" id="apptTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Time</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Amount</th>
            <th>Paid On</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:32px">No appointments found.</td></tr>
          <?php else: ?>
          <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td style="color:var(--muted);font-size:.72rem"><?= $i+1 ?></td>
            <td style="font-weight:500"><?= htmlspecialchars($r['appointment_date']) ?></td>
            <td><?= htmlspecialchars($r['appointment_time']) ?></td>
            <td><?= htmlspecialchars($r['patient_name']) ?></td>
            <td style="color:var(--teal)"><?= htmlspecialchars($r['doctor_name']) ?></td>
            <td style="color:var(--muted);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r['reason'] ?? '—') ?></td>
            <td><span class="ha-badge badge-<?= strtolower($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
            <td><span class="ha-badge badge-<?= strtolower($r['payment_status']) ?>"><?= ucfirst($r['payment_status']) ?></span></td>
            <td><?= $r['amount'] ? 'KES '.number_format($r['amount'],2) : '—' ?></td>
            <td style="color:var(--muted)"><?= $r['payment_date'] ?? '—' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>

    <div class="ha-page-info" style="text-align:right">Showing <?= count($rows) ?> of 50 max results</div>

  </main>
</div><!-- /main-wrap -->

<script>
// Live search filter on table
document.getElementById('searchInput').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#apptTable tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>