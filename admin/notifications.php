<?php
require_once "admin_auth.php";
require_once "../config/db.php";

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'notifications';

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1");
    header("Location: notifications.php");
    exit;
}

// Mark single as read
if (isset($_GET['read_id'])) {
    $rid = (int)$_GET['read_id'];
    $s = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    $s->bind_param('i', $rid);
    $s->execute();
    // redirect back preserving filter
    header("Location: notifications.php");
    exit;
}

// Delete notification
if (isset($_GET['delete_id'])) {
    $did = (int)$_GET['delete_id'];
    $s = $conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
    $s->bind_param('i', $did);
    $s->execute();
    header("Location: notifications.php");
    exit;
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = match($filter) {
    'unread' => 'WHERE is_read = 0',
    'read'   => 'WHERE is_read = 1',
    default  => ''
};

$notifications = $conn->query("
    SELECT * FROM notifications
    $where
    ORDER BY created_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

// Re-fetch count after potential mark-read
$nr = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read = 0");
$notif_count = (int)$nr->fetch_assoc()['c'];

$total_notifs  = $conn->query("SELECT COUNT(*) AS c FROM notifications")->fetch_assoc()['c'];
$unread_notifs = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read = 0")->fetch_assoc()['c'];

// Icon map based on type
function notifIcon(string $type): string {
    return match(true) {
        str_contains($type, 'appointment') => 'fas fa-calendar-check',
        str_contains($type, 'payment')     => 'fas fa-credit-card',
        str_contains($type, 'cancel')      => 'fas fa-calendar-xmark',
        str_contains($type, 'feedback')    => 'fas fa-star',
        str_contains($type, 'user')        => 'fas fa-user',
        str_contains($type, 'system')      => 'fas fa-gear',
        default                            => 'fas fa-bell'
    };
}
function notifColor(string $type): string {
    return match(true) {
        str_contains($type, 'cancel')  => 'var(--rose)',
        str_contains($type, 'payment') => 'var(--green)',
        str_contains($type, 'system')  => 'var(--amber)',
        default                        => 'var(--teal)'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — HealthAdmin</title>
<?php include "sidebar.php"; ?>
<style>
.notif-item {
  display: flex; align-items: flex-start; gap: 14px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  transition: background .15s;
  position: relative;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: var(--surface2); }
.notif-item.unread { background: rgba(14,196,164,.03); }
.notif-item.unread::before {
  content: '';
  position: absolute; left: 0; top: 50%; transform: translateY(-50%);
  width: 3px; height: 40%; background: var(--teal); border-radius: 0 3px 3px 0;
}
.notif-icon-wrap {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: .9rem;
}
.notif-body { flex: 1; min-width: 0; }
.notif-title { font-size: .85rem; font-weight: 600; margin-bottom: 3px; }
.notif-msg   { font-size: .78rem; color: var(--muted); line-height: 1.4; }
.notif-time  { font-size: .7rem; color: var(--muted); margin-top: 5px; display: flex; align-items: center; gap: 4px; }
.notif-actions { display: flex; gap: 6px; align-items: center; margin-left: auto; flex-shrink: 0; }
</style>
</head>
<body>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Notifications</div>
      <div class="topbar-crumb"><a href="dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Notifications</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <?php if ($unread_notifs > 0): ?>
      <a href="?mark_all_read=1" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-check-double"></i> Mark all read</a>
      <?php endif; ?>
      <a href="settings.php" class="topbar-icon-btn"><i class="fas fa-cog"></i></a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-bell"></i> Notifications</h2>
      <p>System alerts and activity notifications.</p>
    </div>

    <!-- Stats -->
    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-bell"></i></div><div><div class="mini-stat-val"><?= $total_notifs ?></div><div class="mini-stat-lbl">Total</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-circle-dot"></i></div><div><div class="mini-stat-val"><?= $unread_notifs ?></div><div class="mini-stat-lbl">Unread</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-check-circle"></i></div><div><div class="mini-stat-val"><?= $total_notifs - $unread_notifs ?></div><div class="mini-stat-lbl">Read</div></div></div>
    </div>

    <!-- Filter tabs -->
    <div style="display:flex;gap:6px;margin-bottom:18px">
      <?php foreach (['all'=>'All','unread'=>'Unread','read'=>'Read'] as $v=>$l): ?>
      <a href="?filter=<?=$v?>" class="ha-btn ha-btn-ghost ha-btn-sm"
         style="<?=($filter===$v)?'border-color:var(--teal);color:var(--teal)':''?>"><?=$l?></a>
      <?php endforeach; ?>
    </div>

    <div class="ha-card" style="padding:0;overflow:hidden">
      <?php if (empty($notifications)): ?>
      <div style="text-align:center;padding:48px;color:var(--muted)">
        <i class="fas fa-bell-slash" style="font-size:2rem;margin-bottom:12px;display:block;opacity:.4"></i>
        No notifications to display.
      </div>
      <?php else: ?>
      <?php foreach ($notifications as $n):
        $type  = $n['type'] ?? 'system';
        $icon  = notifIcon($type);
        $color = notifColor($type);
        $bg    = "rgba(".($color==='var(--rose)'?'240,91,112':($color==='var(--green)'?'52,201,125':($color==='var(--amber)'?'245,166,35':'14,196,164'))).",.15)";
        $unread = !$n['is_read'];
        $timeAgo = $n['created_at'];
      ?>
      <div class="notif-item <?= $unread?'unread':'' ?>">
        <div class="notif-icon-wrap" style="background:<?=$bg?>;color:<?=$color?>">
          <i class="<?=$icon?>"></i>
        </div>
        <div class="notif-body">
          <div class="notif-title">
            <?= htmlspecialchars($n['title'] ?? ucfirst($type).' Notification') ?>
            <?php if($unread): ?><span class="ha-badge badge-scheduled" style="font-size:.6rem;margin-left:6px">New</span><?php endif; ?>
          </div>
          <div class="notif-msg"><?= htmlspecialchars($n['message'] ?? '') ?></div>
          <div class="notif-time"><i class="fas fa-clock"></i> <?= $timeAgo ?></div>
        </div>
        <div class="notif-actions">
          <?php if($unread): ?>
          <a href="?read_id=<?=$n['notification_id']?>&filter=<?=$filter?>" class="ha-btn ha-btn-ghost ha-btn-sm" title="Mark as read"><i class="fas fa-check"></i></a>
          <?php endif; ?>
          <a href="?delete_id=<?=$n['notification_id']?>&filter=<?=$filter?>" class="ha-btn ha-btn-danger ha-btn-sm" title="Delete"
             onclick="return confirm('Delete this notification?')"><i class="fas fa-trash"></i></a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>
</div>
</body>
</html>