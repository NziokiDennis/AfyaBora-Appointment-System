<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$doctor_name = $_SESSION["full_name"];
$current_page = "dashboard";

$spec_stmt = $conn->prepare("SELECT specialization FROM users WHERE user_id = ?");
$spec_stmt->bind_param("i", $user_id);
$spec_stmt->execute();
$doctor_specialization = $spec_stmt->get_result()->fetch_assoc()["specialization"] ?? null;

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
$week = [];
$start = new DateTime();
// DateTime::modify('sunday this week') resolves to *next* Sunday when today isn't
// Sunday, because PHP's "this week" is the ISO Mon-Sun window and Sunday is its
// last day, not its first. Step back to the Sunday that starts the current
// Sun-Sat display week instead.
$start->modify('-' . $start->format('w') . ' days');
for ($i = 0; $i < 7; $i++) {
    $date = $start->format('Y-m-d');
    $week[$date] = ['dow' => (int)$start->format('w'), 'appointments' => [], 'unavail' => []];
    $start->modify('+1 day');
}

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
$upcoming_count = $upcoming_appointments->num_rows;

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
$records_count = $medical_records->num_rows;

// total unique patients seen
$stmt = $conn->prepare("SELECT COUNT(DISTINCT a.patient_id) AS c FROM appointments a WHERE a.doctor_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patients_count = (int)($stmt->get_result()->fetch_assoc()["c"] ?? 0);

$days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .ab-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; }
        @media (max-width: 1100px) { .ab-grid { grid-template-columns: 1fr; } }
        .ab-stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
        @media (max-width: 700px) { .ab-stat-row { grid-template-columns: 1fr; } }
        .ab-stat-tile { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px 20px; display: flex; align-items: center; gap: 14px; }
        .ab-stat-tile .ab-icon-chip { width: 44px; height: 44px; font-size: 1.05rem; }
        .ab-stat-tile .ab-stat-num { font-size: 1.5rem; font-weight: 800; color: var(--navy); line-height: 1.1; }
        .ab-stat-tile .ab-stat-label { font-size: .78rem; color: var(--muted); margin-top: 2px; }
        .ab-section-gap { margin-bottom: 20px; }
        .week-table { width: 100%; border-collapse: collapse; font-size: .74rem; }
        .week-table th, .week-table td { padding: 6px 4px; text-align: center; border-bottom: 1px solid var(--border); }
        .week-table th { color: var(--muted); font-weight: 600; }
        .wk-badge { display: inline-block; padding: 2px 8px; border-radius: var(--radius-pill); font-size: .68rem; font-weight: 700; }
        .wk-free { background: rgba(31,174,122,.12); color: var(--green); }
        .wk-appt { background: rgba(220,38,38,.12); color: var(--rose); }
        .wk-unavail { background: rgba(245,158,11,.14); color: #b45309; }
        .wk-off { background: var(--canvas); color: var(--muted); }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Dr. <?= htmlspecialchars($doctor_name) ?></div>
            <div class="ab-subgreeting"><?= $doctor_specialization ? htmlspecialchars($doctor_specialization) : 'Doctor Portal' ?></div>
        </div>
        <div class="ab-topbar-right">
            <a href="schedule.php" class="ab-btn ab-btn-secondary"><i class="fas fa-clock"></i> Manage Schedule</a>
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($doctor_name, 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($doctor_name) ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">Dashboard</div>
        <div class="ab-page-sub">Your appointments, patients, and records at a glance.</div>

        <div class="ab-stat-row">
            <div class="ab-stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-calendar-check"></i></div>
                <div><div class="ab-stat-num"><?= $upcoming_count ?></div><div class="ab-stat-label">Upcoming Appointments</div></div>
            </div>
            <div class="ab-stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-users"></i></div>
                <div><div class="ab-stat-num"><?= $patients_count ?></div><div class="ab-stat-label">Patients Seen</div></div>
            </div>
            <div class="ab-stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-notes-medical"></i></div>
                <div><div class="ab-stat-num"><?= $records_count ?></div><div class="ab-stat-label">Medical Records</div></div>
            </div>
        </div>

        <div class="ab-grid">
            <div>
                <div class="ab-card ab-section-gap">
                    <div class="ab-card-title"><i class="fas fa-calendar-check"></i> Upcoming Appointments</div>
                    <?php if ($upcoming_count > 0): ?>
                        <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                            <div class="ab-list-row">
                                <div class="ab-icon-chip"><i class="fas fa-user"></i></div>
                                <div class="alr-body">
                                    <div class="alr-title-line">
                                        <span class="alr-title"><?= htmlspecialchars($appointment['patient_name']) ?></span>
                                        <span class="ab-pill ab-pill-neutral"><?= htmlspecialchars($appointment['reason'] ?: 'General') ?></span>
                                    </div>
                                    <div class="alr-meta"><?= date('D, M j, Y', strtotime($appointment['appointment_date'])) ?> · <?= date('g:i A', strtotime($appointment['appointment_time'])) ?></div>
                                </div>
                                <div class="alr-trailing">
                                    <a href="add_medical_record.php?appointment_id=<?= (int)$appointment['appointment_id'] ?>" class="ab-btn ab-btn-primary ab-btn-sm"><i class="fas fa-plus"></i> Add Record</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color:var(--muted);font-size:.85rem;margin:10px 0 0">No upcoming appointments.</p>
                    <?php endif; ?>
                </div>

                <div class="ab-card">
                    <div class="ab-card-title"><i class="fas fa-notes-medical"></i> Completed Medical Records</div>
                    <?php if ($records_count > 0): ?>
                        <?php while ($record = $medical_records->fetch_assoc()): ?>
                            <div class="ab-list-row">
                                <div class="ab-icon-chip"><i class="fas fa-file-medical"></i></div>
                                <div class="alr-body">
                                    <div class="alr-title-line">
                                        <span class="alr-title"><?= htmlspecialchars($record['patient_name']) ?></span>
                                        <span class="alr-meta"><?= date('M j, Y', strtotime($record['appointment_date'])) ?></span>
                                    </div>
                                    <?php if (!empty($record['diagnosis'])): ?><div class="alr-detail"><span class="alr-detail-label">Diagnosis:</span> <?= htmlspecialchars($record['diagnosis']) ?></div><?php endif; ?>
                                    <?php if (!empty($record['prescription'])): ?><div class="alr-detail"><span class="alr-detail-label">Prescription:</span> <?= htmlspecialchars($record['prescription']) ?></div><?php endif; ?>
                                </div>
                                <div class="alr-trailing">
                                    <a href="add_medical_record.php?appointment_id=<?= (int)$record['appointment_id'] ?>" class="ab-btn ab-btn-secondary ab-btn-sm"><i class="fas fa-pen"></i></a>
                                    <a href="delete_medical_record.php?appointment_id=<?= (int)$record['appointment_id'] ?>" class="ab-btn ab-btn-danger ab-btn-sm" onclick="return confirm('Delete this record?');"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color:var(--muted);font-size:.85rem;margin:10px 0 0">No completed medical records yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="ab-card ab-section-gap">
                    <div class="ab-card-title" style="display:flex;justify-content:space-between;align-items:center">
                        <span><i class="fas fa-calendar-days"></i> Your Availability</span>
                        <a href="schedule.php" class="ab-btn ab-btn-secondary ab-btn-sm"><i class="fas fa-clock"></i> Manage</a>
                    </div>
                    <?php if (!empty($schedules_summary)): ?>
                        <?php
                            $sorted_summary = $schedules_summary;
                            usort($sorted_summary, fn($a, $b) => $a['day_of_week'] <=> $b['day_of_week']);
                        ?>
                        <?php foreach ($sorted_summary as $s): ?>
                            <div class="ab-list-row">
                                <div class="ab-icon-chip"><i class="fas fa-calendar-check"></i></div>
                                <div class="alr-body">
                                    <div class="alr-title-line"><span class="alr-title"><?= $days[$s['day_of_week']] ?></span></div>
                                    <div class="alr-meta"><?= substr($s['start_time'],0,5) ?> – <?= substr($s['end_time'],0,5) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--muted);font-size:.85rem;margin:10px 0 0">No working hours set yet. <a href="schedule.php" style="color:var(--blue)">Set your availability</a>.</p>
                    <?php endif; ?>
                </div>

                <div class="ab-card">
                    <div class="ab-card-title"><i class="fas fa-calendar-week"></i> Week at a Glance</div>
                    <div style="overflow-x:auto">
                    <table class="week-table">
                        <thead>
                            <tr>
                                <th>Hr</th>
                                <?php foreach (array_keys($week) as $d): ?>
                                    <th><?= date('D', strtotime($d)) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($hour = 8; $hour <= 17; $hour++): ?>
                            <tr>
                                <td><?= sprintf('%02d', $hour) ?></td>
                                <?php foreach (array_keys($week) as $d): ?>
                                    <?php
                                        $cls = 'wk-off'; $label = '—';
                                        $daySched = null;
                                        foreach ($schedules_summary as $ss) {
                                            if ($ss['day_of_week'] == $week[$d]['dow']) { $daySched = $ss; break; }
                                        }
                                        if ($daySched) {
                                            $hTimestamp = sprintf('%02d:00:00', $hour);
                                            if ($hTimestamp >= $daySched['start_time'] && $hTimestamp <= $daySched['end_time']) {
                                                $cls = 'wk-free'; $label = '·';
                                                foreach ($week[$d]['unavail'] as $u) {
                                                    if ($hTimestamp >= $u['start_time'] && $hTimestamp < $u['end_time']) { $cls = 'wk-unavail'; $label = '·'; break; }
                                                }
                                                if ($cls === 'wk-free' && in_array($hTimestamp, $week[$d]['appointments'])) { $cls = 'wk-appt'; $label = '·'; }
                                            }
                                        }
                                    ?>
                                    <td><span class="wk-badge <?= $cls ?>"><?= $label ?></span></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    </div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;font-size:.72rem;color:var(--muted)">
                        <span><span class="wk-badge wk-free">·</span> Free</span>
                        <span><span class="wk-badge wk-appt">·</span> Booked</span>
                        <span><span class="wk-badge wk-unavail">·</span> Unavailable</span>
                        <span><span class="wk-badge wk-off">·</span> Off hours</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
