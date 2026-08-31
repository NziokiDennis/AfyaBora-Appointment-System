<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$doctor_id = $_SESSION['user_id'];
$current_page = "schedule";
$message = '';
$error = '';

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule'])) {
        $day = intval($_POST['day_of_week']);
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        if ($start >= $end) {
            $error = 'Start time must be before end time.';
        } else {
            $stmt = $conn->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $doctor_id, $day, $start, $end);
            if ($stmt->execute()) {
                $message = 'Schedule added.';
            } elseif ($conn->errno === 1062) {
                $error = 'You already have this exact working-hours entry for ' . dayName($day) . '.';
            } else {
                $error = 'Could not add schedule.';
            }
        }
    }
    if (isset($_POST['add_unavail'])) {
        $date = $_POST['date'];
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $reason = trim($_POST['reason']);
        if ($start >= $end) {
            $error = 'Start time must be before end time.';
        } else {
            $stmt = $conn->prepare("INSERT INTO doctor_unavailability (doctor_id, date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $doctor_id, $date, $start, $end, $reason);
            if ($stmt->execute()) {
                $message = 'Unavailability added.';
            } elseif ($conn->errno === 1062) {
                $error = 'You already have this exact unavailability entry for that date.';
            } else {
                $error = 'Could not add unavailability.';
            }
        }
    }
}

// handle deletions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete']) && isset($_GET['type'])) {
    $id = intval($_GET['delete']);
    if ($_GET['type'] === 'sched') {
        $stmt = $conn->prepare("DELETE FROM doctor_schedules WHERE schedule_id=? AND doctor_id=?");
        $stmt->bind_param("ii", $id, $doctor_id);
        $stmt->execute();
        $message = 'Schedule removed.';
    } elseif ($_GET['type'] === 'unavail') {
        $stmt = $conn->prepare("DELETE FROM doctor_unavailability WHERE id=? AND doctor_id=?");
        $stmt->bind_param("ii", $id, $doctor_id);
        $stmt->execute();
        $message = 'Unavailability removed.';
    }
}

// fetch current data
$schedules = [];
$res = $conn->prepare("SELECT schedule_id, day_of_week, start_time, end_time FROM doctor_schedules WHERE doctor_id=?");
$res->bind_param("i", $doctor_id);
$res->execute();
$sch = $res->get_result();
while ($r = $sch->fetch_assoc()) {
    $schedules[] = $r;
}
$unav = [];
$res = $conn->prepare("SELECT id, date, start_time, end_time, reason FROM doctor_unavailability WHERE doctor_id=?");
$res->bind_param("i", $doctor_id);
$res->execute();
$ua = $res->get_result();
while ($r = $ua->fetch_assoc()) {
    $unav[] = $r;
}

function dayName($d) {
    $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    return $days[$d];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sched-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
        @media (max-width: 900px) { .sched-grid { grid-template-columns: 1fr; } }
        .sched-remove { color: var(--rose); font-size: .78rem; font-weight: 600; }
        .sched-remove:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Schedule Management</div>
            <div class="ab-subgreeting">Working hours and unavailable dates</div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">Schedule Management</div>
        <div class="ab-page-sub">Define your weekly working hours and any dates/times you will not be available.</div>

        <?php if ($message): ?><div class="ab-alert ab-alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="ab-alert ab-alert-danger"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="sched-grid">
            <div>
                <div class="ab-card" style="margin-bottom:18px">
                    <div class="ab-card-title"><i class="fas fa-clock"></i> Working Hours</div>
                    <?php if ($schedules): ?>
                        <?php foreach ($schedules as $s): ?>
                        <div class="ab-list-row">
                            <div class="ab-icon-chip"><i class="fas fa-calendar-day"></i></div>
                            <div class="alr-body">
                                <div class="alr-title-line"><span class="alr-title"><?= dayName($s['day_of_week']) ?></span></div>
                                <div class="alr-meta"><?= substr($s['start_time'],0,5) ?> – <?= substr($s['end_time'],0,5) ?></div>
                            </div>
                            <div class="alr-trailing">
                                <a href="schedule.php?delete=<?= (int)$s['schedule_id'] ?>&type=sched" class="sched-remove" onclick="return confirm('Remove this?');">Remove</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--muted);font-size:.85rem;margin:6px 0 0">No working hours set yet.</p>
                    <?php endif; ?>
                </div>

                <div class="ab-card">
                    <div class="ab-card-title"><i class="fas fa-plus"></i> Add Working Hours</div>
                    <form method="POST">
                        <input type="hidden" name="add_schedule" value="1">
                        <div class="ab-form-group">
                            <label class="ab-label">Day of week</label>
                            <select name="day_of_week" class="ab-input" required>
                                <?php for($i=0;$i<7;$i++): ?>
                                    <option value="<?= $i ?>"><?= dayName($i) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="ab-form-row">
                            <div class="ab-form-group">
                                <label class="ab-label">Start time</label>
                                <input type="time" name="start_time" class="ab-input" required>
                            </div>
                            <div class="ab-form-group">
                                <label class="ab-label">End time</label>
                                <input type="time" name="end_time" class="ab-input" required>
                            </div>
                        </div>
                        <button class="ab-btn ab-btn-primary" style="width:100%;justify-content:center"><i class="fas fa-plus"></i> Add Hours</button>
                    </form>
                </div>
            </div>

            <div>
                <div class="ab-card" style="margin-bottom:18px">
                    <div class="ab-card-title"><i class="fas fa-calendar-xmark"></i> Unavailable Dates/Times</div>
                    <?php if ($unav): ?>
                        <?php foreach ($unav as $u): ?>
                        <div class="ab-list-row">
                            <div class="ab-icon-chip"><i class="fas fa-ban"></i></div>
                            <div class="alr-body">
                                <div class="alr-title-line"><span class="alr-title"><?= date('D, M j, Y', strtotime($u['date'])) ?></span></div>
                                <div class="alr-meta"><?= substr($u['start_time'],0,5) ?> – <?= substr($u['end_time'],0,5) ?></div>
                                <?php if ($u['reason']): ?><div class="alr-detail"><?= htmlspecialchars($u['reason']) ?></div><?php endif; ?>
                            </div>
                            <div class="alr-trailing">
                                <a href="schedule.php?delete=<?= (int)$u['id'] ?>&type=unavail" class="sched-remove" onclick="return confirm('Remove this?');">Remove</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--muted);font-size:.85rem;margin:6px 0 0">No unavailability recorded.</p>
                    <?php endif; ?>
                </div>

                <div class="ab-card">
                    <div class="ab-card-title"><i class="fas fa-plus"></i> Add Unavailability</div>
                    <form method="POST">
                        <input type="hidden" name="add_unavail" value="1">
                        <div class="ab-form-group">
                            <label class="ab-label">Date</label>
                            <input type="date" name="date" class="ab-input" required>
                        </div>
                        <div class="ab-form-row">
                            <div class="ab-form-group">
                                <label class="ab-label">Start time</label>
                                <input type="time" name="start_time" class="ab-input" required>
                            </div>
                            <div class="ab-form-group">
                                <label class="ab-label">End time</label>
                                <input type="time" name="end_time" class="ab-input" required>
                            </div>
                        </div>
                        <div class="ab-form-group">
                            <label class="ab-label">Reason (optional)</label>
                            <input type="text" name="reason" class="ab-input">
                        </div>
                        <button class="ab-btn ab-btn-primary" style="width:100%;justify-content:center"><i class="fas fa-plus"></i> Add Unavailability</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
