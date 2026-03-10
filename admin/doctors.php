<?php
require_once "admin_auth.php";
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'doctors';

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

$search = trim($_GET['search'] ?? '');
$where  = $search ? "AND (u.full_name LIKE ? OR u.email LIKE ?)" : '';
$params = $search ? ["%$search%", "%$search%"] : [];
$types  = $search ? 'ss' : '';

$sql = "
    SELECT
        u.user_id,
        u.full_name,
        u.email,
        u.phone_number,
        u.created_at,
        COUNT(DISTINCT a.appointment_id)  AS total_appointments,
        SUM(a.status = 'completed')       AS completed,
        SUM(a.status = 'canceled')        AS canceled,
        ROUND(AVG(f.rating), 1)           AS avg_rating,
        MAX(a.appointment_date)           AS last_appointment
    FROM users u
    LEFT JOIN appointments a ON u.user_id = a.doctor_id
    LEFT JOIN feedback f     ON u.user_id = f.doctor_id
    WHERE u.role = 'doctor'
    $where
    GROUP BY u.user_id, u.full_name, u.email, u.phone_number, u.created_at
    ORDER BY total_appointments DESC
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_doctors = (int)$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='doctor'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctors — HealthAdmin</title>
</head>
<body>
<?php include "sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Doctors</div>
      <div class="topbar-crumb"><a href="dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Doctors</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="settings.php" class="topbar-icon-btn"><i class="fas fa-cog"></i></a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-user-md"></i> Doctors</h2>
      <p>All registered doctors and their performance overview.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-user-md"></i></div><div><div class="mini-stat-val"><?= $total_doctors ?></div><div class="mini-stat-lbl">Total Doctors</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-stethoscope"></i></div><div><div class="mini-stat-val"><?= count($doctors) ?></div><div class="mini-stat-lbl">Showing</div></div></div>
    </div>

    <div class="search-wrap">
      <form method="GET" style="display:contents">
        <div class="search-input-wrap">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
        </div>
        <button type="submit" class="ha-btn ha-btn-primary ha-btn-sm"><i class="fas fa-search"></i> Search</button>
        <?php if($search): ?><a href="doctors.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="overflow-x:auto">
      <table class="ha-table" id="doctorsTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Total Appts</th>
            <th>Completed</th>
            <th>Canceled</th>
            <th>Avg Rating</th>
            <th>Last Appointment</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($doctors)): ?>
          <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:32px">No doctors found.</td></tr>
          <?php else: ?>
          <?php foreach ($doctors as $i => $doc): ?>
          <tr>
            <td style="color:var(--muted);font-size:.72rem"><?= $i+1 ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($doc['full_name']) ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($doc['email']) ?></td>
            <td><?= htmlspecialchars($doc['phone_number'] ?? '—') ?></td>
            <td style="text-align:center;font-family:var(--font-mono);font-weight:700"><?= $doc['total_appointments'] ?></td>
            <td style="text-align:center;color:var(--green);font-weight:600"><?= $doc['completed'] ?? 0 ?></td>
            <td style="text-align:center;color:var(--rose);font-weight:600"><?= $doc['canceled'] ?? 0 ?></td>
            <td style="text-align:center">
              <?php if($doc['avg_rating']): ?>
              <span style="color:var(--amber);font-weight:700"><i class="fas fa-star" style="font-size:.7rem"></i> <?= $doc['avg_rating'] ?></span>
              <?php else: echo '<span style="color:var(--muted)">—</span>'; endif; ?>
            </td>
            <td style="color:var(--muted)"><?= $doc['last_appointment'] ?? 'None' ?></td>
            <td style="color:var(--muted);font-size:.75rem"><?= date('M j, Y', strtotime($doc['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>

  </main>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('#doctorsTable tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>