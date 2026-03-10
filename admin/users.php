<?php
require_once "admin_auth.php";
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'users';

// Handle delete FIRST before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)$_POST['delete_id'];
    $ds = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $ds->bind_param('i', $del_id);
    $ds->execute();
    header("Location: users.php?success=deleted");
    exit;
}

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

$search      = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(full_name LIKE ? OR email LIKE ? OR phone_number LIKE ?)";
    $like     = "%$search%";
    $params   = array_merge($params, [$like,$like,$like]);
    $types   .= 'sss';
}
if ($role_filter && $role_filter !== 'all') {
    $where[]  = "role = ?";
    $params[] = $role_filter;
    $types   .= 's';
}
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$sql  = "SELECT user_id, full_name, email, phone_number, role, created_at FROM users $where_sql ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$counts = [];
$cr = $conn->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
while ($row = $cr->fetch_assoc()) $counts[$row['role']] = $row['c'];

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management — HealthAdmin</title>
</head>
<body>
<?php include "sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">User Management</div>
      <div class="topbar-crumb"><a href="dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Users</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="add_user.php" class="ha-btn ha-btn-primary ha-btn-sm"><i class="fas fa-user-plus"></i> Add User</a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-users-cog"></i> User Management</h2>
      <p>Create, manage and control all system accounts.</p>
    </div>

    <?php if ($success === 'added'): ?>
    <div class="ha-alert ha-alert-success"><i class="fas fa-circle-check"></i> User created successfully.</div>
    <?php elseif ($success === 'updated'): ?>
    <div class="ha-alert ha-alert-success"><i class="fas fa-circle-check"></i> User updated successfully.</div>
    <?php elseif ($success === 'deleted'): ?>
    <div class="ha-alert ha-alert-danger"><i class="fas fa-circle-check"></i> User deleted successfully.</div>
    <?php endif; ?>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-users"></i></div><div><div class="mini-stat-val"><?= array_sum($counts) ?></div><div class="mini-stat-lbl">All Users</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-shield-halved"></i></div><div><div class="mini-stat-val"><?= $counts['admin'] ?? 0 ?></div><div class="mini-stat-lbl">Admins</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-user-md"></i></div><div><div class="mini-stat-val"><?= $counts['doctor'] ?? 0 ?></div><div class="mini-stat-lbl">Doctors</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-user-injured"></i></div><div><div class="mini-stat-val"><?= $counts['patient'] ?? 0 ?></div><div class="mini-stat-lbl">Patients</div></div></div>
    </div>

    <div class="search-wrap" style="flex-wrap:wrap">
      <form method="GET" style="display:contents">
        <div class="search-input-wrap">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Search name, email, phone..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
        </div>
        <div style="display:flex;gap:6px">
          <?php foreach (['all'=>'All','admin'=>'Admins','doctor'=>'Doctors','patient'=>'Patients'] as $v=>$l): ?>
          <a href="?role=<?=$v?><?=$search?'&search='.urlencode($search):''?>"
             class="ha-btn ha-btn-ghost ha-btn-sm"
             style="<?=($role_filter===$v||($v==='all'&&!$role_filter))?'border-color:var(--teal);color:var(--teal)':''?>"><?=$l?></a>
          <?php endforeach; ?>
        </div>
        <?php if($search||$role_filter): ?><a href="users.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="overflow-x:auto">
      <table class="ha-table" id="usersTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Registered</th>
            <th style="text-align:center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:32px">No users found.</td></tr>
          <?php else: ?>
          <?php foreach ($users as $i => $u): ?>
          <tr>
            <td style="color:var(--muted);font-size:.72rem"><?= $i+1 ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:9px">
                <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--blue));display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0">
                  <?= strtoupper(substr($u['full_name'],0,2)) ?>
                </div>
                <span style="font-weight:600"><?= htmlspecialchars($u['full_name']) ?></span>
              </div>
            </td>
            <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['phone_number'] ?? '—') ?></td>
            <td><span class="ha-badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
            <td style="color:var(--muted);font-size:.75rem"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td style="text-align:center">
              <div style="display:flex;gap:6px;justify-content:center">
                <a href="edit_user.php?id=<?= $u['user_id'] ?>" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-pen"></i></a>
                <button onclick="confirmDelete(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>')"
                        class="ha-btn ha-btn-danger ha-btn-sm"><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>

    <form method="POST" id="deleteForm" style="display:none">
      <input type="hidden" name="delete_id" id="deleteId">
    </form>

  </main>
</div>

<!-- Delete modal -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:999;align-items:center;justify-content:center">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:32px;max-width:380px;width:90%;text-align:center">
    <div style="width:52px;height:52px;background:rgba(240,91,112,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.3rem;color:var(--rose)"><i class="fas fa-trash"></i></div>
    <h3 style="font-size:1.1rem;margin-bottom:8px">Delete User?</h3>
    <p id="deleteMsg" style="color:var(--muted);font-size:.83rem;margin-bottom:24px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="ha-btn ha-btn-ghost">Cancel</button>
      <button onclick="document.getElementById('deleteForm').submit()" class="ha-btn ha-btn-danger"><i class="fas fa-trash"></i> Delete</button>
    </div>
  </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('#usersTable tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
function confirmDelete(id, name) {
  document.getElementById('deleteId').value = id;
  document.getElementById('deleteMsg').textContent = `"${name}" will be permanently removed from the system.`;
  document.getElementById('deleteModal').style.display = 'flex';
}
</script>
</body>
</html>