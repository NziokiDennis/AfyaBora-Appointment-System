<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$doctor_id = $_SESSION['user_id'];
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
        $conn->query("DELETE FROM doctor_schedules WHERE schedule_id=$id AND doctor_id=$doctor_id");
        $message = 'Schedule removed.';
    } elseif ($_GET['type'] === 'unavail') {
        $conn->query("DELETE FROM doctor_unavailability WHERE id=$id AND doctor_id=$doctor_id");
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
    <title>Manage Schedule</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include "navbar.php"; ?>
    <div class="container mt-5">
        <h2>Schedule Management</h2>
        <p class="text-muted">Define your weekly working hours and any dates/times you will not be available.</p>
        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <h4>Working Hours</h4>
                <table class="table table-sm table-hover">
                    <thead><tr><th>Day</th><th>From</th><th>To</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td><?= dayName($s['day_of_week']) ?></td>
                            <td><?= $s['start_time'] ?></td>
                            <td><?= $s['end_time'] ?></td>
                            <td><a href="schedule.php?delete=<?= $s['schedule_id'] ?>&type=sched" class="btn btn-sm btn-link text-danger" onclick="return confirm('Remove this?');">Remove</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="POST" class="p-3 border rounded bg-light mb-4">
                    <input type="hidden" name="add_schedule" value="1">
                    <div class="mb-2">
                        <label>Day of week</label>
                        <select name="day_of_week" class="form-control" required>
                            <?php for($i=0;$i<7;$i++): ?>
                                <option value="<?= $i ?>"><?= dayName($i) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label>Start time</label><input type="time" name="start_time" class="form-control" required></div>
                    <div class="mb-2"><label>End time</label><input type="time" name="end_time" class="form-control" required></div>
                    <button class="btn btn-primary btn-sm">Add Hours</button>
                </form>
            </div>
            <div class="col-md-6">
                <h4>Unavailable Dates/Times</h4>
                <table class="table table-sm table-hover">
                    <thead><tr><th>Date</th><th>From</th><th>To</th><th>Reason</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($unav as $u): ?>
                        <tr>
                            <td><?= $u['date'] ?></td>
                            <td><?= $u['start_time'] ?></td>
                            <td><?= $u['end_time'] ?></td>
                            <td><?= htmlspecialchars($u['reason']) ?></td>
                            <td><a href="schedule.php?delete=<?= $u['id'] ?>&type=unavail" class="btn btn-sm btn-link text-danger" onclick="return confirm('Remove this?');">Remove</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="POST" class="p-3 border rounded bg-light mb-4">
                    <input type="hidden" name="add_unavail" value="1">
                    <div class="mb-2"><label>Date</label><input type="date" name="date" class="form-control" required></div>
                    <div class="mb-2"><label>Start time</label><input type="time" name="start_time" class="form-control" required></div>
                    <div class="mb-2"><label>End time</label><input type="time" name="end_time" class="form-control" required></div>
                    <div class="mb-2"><label>Reason (optional)</label><input type="text" name="reason" class="form-control"></div>
                    <button class="btn btn-primary btn-sm">Add Unavailability</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>