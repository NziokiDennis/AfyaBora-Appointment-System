<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$doctor_name = $_SESSION["full_name"];

// also load schedule info for summary
$schedules_summary = [];
$stmt = $conn->prepare("SELECT day_of_week, start_time, end_time FROM doctor_schedules WHERE doctor_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $schedules_summary[] = $r;
}

// build simple week grid of appointments and unavailability
// build week starting on Sunday of current week
$week = [];
$start = new DateTime();
// move back to sunday
$start->modify('sunday this week');
for ($i = 0; $i < 7; $i++) {
    $date = $start->format('Y-m-d');
    $week[$date] = ['dow' => (int)$start->format('w'), 'appointments' => [], 'unavail' => []];
    $start->modify('+1 day');
}

// fetch appointments for the next 7 days
$dates = array_keys($week);
$first = $dates[0];
$last = end($dates);
$stmt = $conn->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE doctor_id=? AND appointment_date BETWEEN ? AND ? AND status='scheduled'");
$stmt->bind_param("iss", $user_id, $first, $last);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $week[$r['appointment_date']]['appointments'][] = $r['appointment_time'];
}
// fetch unavailability
$stmt = $conn->prepare("SELECT date,start_time,end_time FROM doctor_unavailability WHERE doctor_id=? AND date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $first, $last);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $week[$r['date']]['unavail'][] = $r;
}

// Fetch upcoming appointments
$query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason, a.additional_notes, u.full_name AS patient_name 
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = ? AND a.status = 'scheduled'
          ORDER BY a.appointment_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();

// Fetch completed medical records
$query = "SELECT a.appointment_id, a.appointment_date, u.full_name AS patient_name, m.diagnosis, m.prescription, m.notes 
          FROM medical_records m
          JOIN appointments a ON m.appointment_id = a.appointment_id
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = ? AND a.status = 'completed'
          ORDER BY a.appointment_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$medical_records = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .dashboard-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .table th, .table td { text-align: center; vertical-align: middle; }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container">
    <h2 class="text-center">Welcome, Dr. <?php echo $doctor_name; ?></h2>
    <div class="text-center mb-3">
        <a href="schedule.php" class="btn btn-sm btn-secondary">Manage Schedule / Time Off</a>
    </div>
    <?php if (!empty($schedules_summary)): ?>
    <div class="text-center mb-4">
        <strong>Weekly hours:</strong>
        <?php
            $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            $lines = [];
            foreach ($schedules_summary as $s) {
                $lines[] = $days[$s['day_of_week']] . ' ' . substr($s['start_time'],0,5) . '-' . substr($s['end_time'],0,5);
            }
        ?>
        <?= implode(', ', $lines) ?>
    </div>
    <?php endif; ?>

    <!-- Week overview calendar -->
    <div class="dashboard-card mb-4">
        <h4 class="text-secondary">Week at a Glance</h4>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Hour</th>
                    <?php foreach (array_keys($week) as $d): ?>
                        <th><?= date('D m/d', strtotime($d)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($hour = 8; $hour <= 17; $hour++): ?>
                <tr>
                    <td><?= sprintf('%02d:00', $hour) ?></td>
                    <?php foreach (array_keys($week) as $d): ?>
                        <?php
                            $cell = '';
                            // check unavailability
                                        // if no schedule defined for that weekday, mark all hours not available
                            $daySched = null;
                            foreach ($schedules_summary as $ss) {
                                if ($ss['day_of_week'] == $week[$d]['dow']) {
                                    $daySched = $ss;
                                    break;
                                }
                            }
                            if (!$daySched) {
                                $cell = '<span class="badge bg-secondary">Not Available</span>';
                            } else {
                                // first ensure hour falls within working hours (inclusive end)
                                $hTimestamp = sprintf('%02d:00:00',$hour);
                                if ($hTimestamp < $daySched['start_time'] || $hTimestamp > $daySched['end_time']) {
                                    $cell = '<span class="badge bg-secondary">Not Available</span>';
                                } else {
                                    // check unavailability blocks
                                    foreach ($week[$d]['unavail'] as $u) {
                                        if ($hTimestamp >= $u['start_time'] && $hTimestamp < $u['end_time']) {
                                            $cell = '<span class="badge bg-warning">Not Available</span>';
                                            break;
                                        }
                                    }
                                    if ($cell === '' && in_array($hTimestamp, $week[$d]['appointments'])) {
                                        $cell = '<span class="badge bg-danger">Appt</span>';
                                    }
                                    if ($cell === '') {
                                        $cell = '<span class="badge bg-success">Free</span>';
                                    }
                                }
                            }
                        ?>
                        <td><?= $cell ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <!-- Upcoming Appointments -->
    <div class="dashboard-card mt-4">
        <h4 class="text-primary">Upcoming Appointments</h4>
        <?php if ($upcoming_appointments->num_rows > 0): ?>
            <table class="table table-bordered table-hover mt-3">
                <thead class="table-primary">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Patient</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $appointment["appointment_date"]; ?></td>
                            <td><?php echo $appointment["appointment_time"]; ?></td>
                            <td><?php echo htmlspecialchars($appointment["reason"] ?? ''); ?></td>
                            <td><?php echo $appointment["patient_name"]; ?></td>
                            <td>
                                <a href="add_medical_record.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-primary">Add Record</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mt-3">No upcoming appointments.</p>
        <?php endif; ?>
    </div>

    <!-- Medical Records -->
    <div class="dashboard-card">
        <h4 class="text-secondary">Completed Medical Records</h4>
        <?php if ($medical_records->num_rows > 0): ?>
            <table class="table table-bordered table-hover mt-3">
                <thead class="table-secondary">
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Diagnosis</th>
                        <th>Prescription</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($record = $medical_records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $record["appointment_date"]; ?></td>
                            <td><?php echo $record["patient_name"]; ?></td>
                            <td><?php echo $record["diagnosis"]; ?></td>
                            <td><?php echo $record["prescription"]; ?></td>
                            <td><?php echo $record["notes"]; ?></td>
                            <td>
                                <a href="add_medical_record.php?appointment_id=<?php echo $record['appointment_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="delete_medical_record.php?appointment_id=<?php echo $record['appointment_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this record?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mt-3">No completed medical records yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include "../partials/footer.php"; ?>

</body>
</html>
