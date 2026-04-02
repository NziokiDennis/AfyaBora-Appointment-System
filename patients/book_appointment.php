<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$success = "";
$error = "";

function formatTimeDisplay($time) {
    return date("g:i A", strtotime($time));
}

function formatDateDisplay($date) {
    return date("D, M j, Y", strtotime($date));
}

function formatDoctorScheduleText($scheduleRows) {
    if (empty($scheduleRows)) {
        return "No working hours have been defined for this doctor yet.";
    }

    $days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    usort($scheduleRows, function ($a, $b) {
        if ($a["day_of_week"] == $b["day_of_week"]) {
            return strcmp($a["start_time"], $b["start_time"]);
        }
        return $a["day_of_week"] <=> $b["day_of_week"];
    });

    $parts = [];
    foreach ($scheduleRows as $row) {
        $parts[] = $days[(int)$row["day_of_week"]] . " " . substr($row["start_time"], 0, 5) . "-" . substr($row["end_time"], 0, 5);
    }

    return implode(", ", $parts);
}

function getDoctorScheduleRowsForDay($conn, $doctor_id, $date) {
    $dow = (int)date("w", strtotime($date));
    $stmt = $conn->prepare("SELECT start_time, end_time FROM doctor_schedules WHERE doctor_id=? AND day_of_week=? ORDER BY start_time ASC");
    $stmt->bind_param("ii", $doctor_id, $dow);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function getDoctorAppointmentsForDay($conn, $doctor_id, $date) {
    $stmt = $conn->prepare("
        SELECT appointment_time, appointment_duration
        FROM appointments
        WHERE doctor_id = ?
          AND appointment_date = ?
          AND status = 'scheduled'
        ORDER BY appointment_time ASC
    ");
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    return $appointments;
}

function timeFallsWithinSchedules($startTs, $endTs, $scheduleRows, $date) {
    foreach ($scheduleRows as $row) {
        $scheduleStart = strtotime($date . " " . $row["start_time"]);
        $scheduleEnd = strtotime($date . " " . $row["end_time"]);
        if ($startTs >= $scheduleStart && $endTs <= $scheduleEnd) {
            return true;
        }
    }

    return false;
}

function hasUnavailabilityOverlap($conn, $doctor_id, $date, $startTs, $endTs) {
    $startTime = date("H:i:s", $startTs);
    $endTime = date("H:i:s", $endTs);

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM doctor_unavailability
        WHERE doctor_id = ?
          AND date = ?
          AND start_time < ?
          AND end_time > ?
    ");
    $stmt->bind_param("isss", $doctor_id, $date, $endTime, $startTime);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()["cnt"];

    return $count > 0;
}

function hasAppointmentOverlap($appointments, $startTs, $endTs, $date) {
    foreach ($appointments as $appointment) {
        $appointmentStart = strtotime($date . " " . $appointment["appointment_time"]);
        $duration = max(1, (int)($appointment["appointment_duration"] ?? 30));
        $appointmentEnd = strtotime("+" . $duration . " minutes", $appointmentStart);

        if ($startTs < $appointmentEnd && $endTs > $appointmentStart) {
            return true;
        }
    }

    return false;
}

function isDoctorSlotAvailable($conn, $doctor_id, $date, $time, $duration = 30) {
    $scheduleRows = getDoctorScheduleRowsForDay($conn, $doctor_id, $date);
    if (empty($scheduleRows)) {
        return false;
    }

    $startTs = strtotime($date . " " . $time);
    $endTs = strtotime("+" . $duration . " minutes", $startTs);
    if (!timeFallsWithinSchedules($startTs, $endTs, $scheduleRows, $date)) {
        return false;
    }

    if (hasUnavailabilityOverlap($conn, $doctor_id, $date, $startTs, $endTs)) {
        return false;
    }

    $appointments = getDoctorAppointmentsForDay($conn, $doctor_id, $date);
    if (hasAppointmentOverlap($appointments, $startTs, $endTs, $date)) {
        return false;
    }

    return true;
}

function findNextAvailableSlot($conn, $doctor_id, $startDate, $duration = 30, $preferredTime = null, $searchDays = 30) {
    $today = date("Y-m-d");
    $nowTs = time();

    for ($offset = 0; $offset <= $searchDays; $offset++) {
        $date = date("Y-m-d", strtotime($startDate . " +$offset day"));
        $scheduleRows = getDoctorScheduleRowsForDay($conn, $doctor_id, $date);
        if (empty($scheduleRows)) {
            continue;
        }

        $appointments = getDoctorAppointmentsForDay($conn, $doctor_id, $date);
        $minStartTs = null;

        if ($offset === 0 && $preferredTime) {
            $minStartTs = strtotime($date . " " . $preferredTime);
        } elseif ($date === $today) {
            $roundedNow = ceil($nowTs / 1800) * 1800;
            $minStartTs = $roundedNow;
        }

        foreach ($scheduleRows as $row) {
            $slotStart = strtotime($date . " " . $row["start_time"]);
            $slotEnd = strtotime($date . " " . $row["end_time"]);

            if ($minStartTs !== null && $minStartTs > $slotStart) {
                $slotStart = $minStartTs;
            }

            $minute = (int)date("i", $slotStart);
            $remainder = $minute % 30;
            if ($remainder !== 0) {
                $slotStart = strtotime("+" . (30 - $remainder) . " minutes", $slotStart);
            }

            while (strtotime("+" . $duration . " minutes", $slotStart) <= $slotEnd) {
                $candidateEnd = strtotime("+" . $duration . " minutes", $slotStart);

                if (
                    !hasUnavailabilityOverlap($conn, $doctor_id, $date, $slotStart, $candidateEnd) &&
                    !hasAppointmentOverlap($appointments, $slotStart, $candidateEnd, $date)
                ) {
                    return [
                        "date" => $date,
                        "time" => date("H:i:s", $slotStart),
                    ];
                }

                $slotStart = strtotime("+30 minutes", $slotStart);
            }
        }
    }

    return null;
}

function buildAvailabilityMessage($conn, $doctor_id, $appointment_date, $appointment_time, $duration, $allSchedules) {
    $scheduleText = "Working hours: " . ($allSchedules[$doctor_id] ?? null ? formatDoctorScheduleText($allSchedules[$doctor_id]) : "No working hours have been defined for this doctor yet.");
    $daySchedules = getDoctorScheduleRowsForDay($conn, $doctor_id, $appointment_date);

    if (empty($daySchedules)) {
        $nextSlot = findNextAvailableSlot($conn, $doctor_id, date("Y-m-d", strtotime($appointment_date . " +1 day")), $duration);
        $message = "Doctor is not available on " . formatDateDisplay($appointment_date) . ".";
        if ($nextSlot) {
            $message .= " Please book on the next available slot: " . formatDateDisplay($nextSlot["date"]) . " at " . formatTimeDisplay($nextSlot["time"]) . ".";
        }
        return $scheduleText . " " . $message;
    }

    $requestedStart = strtotime($appointment_date . " " . $appointment_time);
    $requestedEnd = strtotime("+" . $duration . " minutes", $requestedStart);
    if (!timeFallsWithinSchedules($requestedStart, $requestedEnd, $daySchedules, $appointment_date)) {
        $sameDaySlot = findNextAvailableSlot($conn, $doctor_id, $appointment_date, $duration, $appointment_time);
        $message = "Doctor is not available at the requested date/time.";
        if ($sameDaySlot && $sameDaySlot["date"] === $appointment_date) {
            $message .= " The next available time on " . formatDateDisplay($appointment_date) . " is " . formatTimeDisplay($sameDaySlot["time"]) . ".";
        } else {
            $nextSlot = findNextAvailableSlot($conn, $doctor_id, date("Y-m-d", strtotime($appointment_date . " +1 day")), $duration);
            if ($nextSlot) {
                $message .= " The next available slot is " . formatDateDisplay($nextSlot["date"]) . " at " . formatTimeDisplay($nextSlot["time"]) . ".";
            }
        }
        return $scheduleText . " " . $message;
    }

    if (hasUnavailabilityOverlap($conn, $doctor_id, $appointment_date, $requestedStart, $requestedEnd)) {
        $sameDaySlot = findNextAvailableSlot($conn, $doctor_id, $appointment_date, $duration, $appointment_time);
        $message = "Doctor is marked unavailable at the requested time.";
        if ($sameDaySlot && $sameDaySlot["date"] === $appointment_date) {
            $message .= " Please book from " . formatTimeDisplay($sameDaySlot["time"]) . " on the same day.";
        } else {
            $nextSlot = findNextAvailableSlot($conn, $doctor_id, date("Y-m-d", strtotime($appointment_date . " +1 day")), $duration);
            if ($nextSlot) {
                $message .= " Please try " . formatDateDisplay($nextSlot["date"]) . " at " . formatTimeDisplay($nextSlot["time"]) . ".";
            }
        }
        return $scheduleText . " " . $message;
    }

    $appointments = getDoctorAppointmentsForDay($conn, $doctor_id, $appointment_date);
    if (hasAppointmentOverlap($appointments, $requestedStart, $requestedEnd, $appointment_date)) {
        $sameDaySlot = findNextAvailableSlot($conn, $doctor_id, $appointment_date, $duration, $appointment_time);
        $message = "Doctor is already booked at the requested time.";
        if ($sameDaySlot && $sameDaySlot["date"] === $appointment_date) {
            $message .= " The next available time on that day is " . formatTimeDisplay($sameDaySlot["time"]) . ".";
        } else {
            $nextSlot = findNextAvailableSlot($conn, $doctor_id, date("Y-m-d", strtotime($appointment_date . " +1 day")), $duration);
            if ($nextSlot) {
                $message .= " The doctor appears fully booked for that day. Please book on " . formatDateDisplay($nextSlot["date"]) . " at " . formatTimeDisplay($nextSlot["time"]) . ".";
            } else {
                $message .= " The doctor appears fully booked for that day. Please choose another available day.";
            }
        }
        return $scheduleText . " " . $message;
    }

    return $scheduleText . " Doctor is not available at the requested date/time.";
}

// Fetch available doctors
$doctors_query = "SELECT user_id, full_name FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($doctors_query);

// also grab scheduling information for use in UI/validation
$schedules = [];
$unavailability = [];
$sched_q = "SELECT doctor_id, day_of_week, start_time, end_time FROM doctor_schedules";
if ($res = $conn->query($sched_q)) {
    while ($r = $res->fetch_assoc()) {
        $schedules[$r['doctor_id']][] = $r;
    }
}
$unavail_q = "SELECT doctor_id, date, start_time, end_time FROM doctor_unavailability";
if ($res = $conn->query($unavail_q)) {
    while ($r = $res->fetch_assoc()) {
        $unavailability[$r['doctor_id']][] = $r;
    }
}

// Handle appointment booking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = (int)$_POST["doctor_id"];
    $appointment_date = $_POST["appointment_date"];
    $appointment_time = $_POST["appointment_time"];
    $reason = $_POST["reason"];
    $additional_notes = trim($_POST["additional_notes"]);
    $appointment_duration = 30;

    // Validate selected date (must not be in the past)
    $current_date = date("Y-m-d");
    if ($appointment_date < $current_date) {
        $error = "You cannot book an appointment for a past date.";
    } elseif (!isDoctorSlotAvailable($conn, $doctor_id, $appointment_date, $appointment_time, $appointment_duration)) {
        $error = buildAvailabilityMessage($conn, $doctor_id, $appointment_date, $appointment_time, $appointment_duration, $schedules);
    } else {
        // Get patient ID from `patients` table
        $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $patient_result = $stmt->get_result();
        $patient = $patient_result->fetch_assoc();

        // If patient record does not exist, create it
        if (!$patient) {
            $stmt = $conn->prepare("INSERT INTO patients (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $patient_id = $conn->insert_id; // Get newly inserted patient ID
        } else {
            $patient_id = $patient["patient_id"];
        }

        // Insert appointment
        $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, additional_notes, appointment_duration) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iissssi", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $additional_notes, $appointment_duration);

        if ($stmt->execute()) {
            $appointment_id = $conn->insert_id;
            // Redirect to payment page
            header("Location: payment.php?appointment_id=" . $appointment_id);
            exit();
        } else {
            $error = "Error booking appointment.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .appointment-card {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-primary { width: 100%; }
        .schedule-banner {
            background: #eef6ff;
            border: 1px solid #cfe2ff;
            color: #0c5460;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-size: 0.95rem;
        }
        .schedule-banner strong {
            color: #0a58ca;
        }
    </style>
</head>
<body>

    <?php include "navbar.php"; ?>

    <div class="container">
        <div class="appointment-card">
            <h2 class="text-center">Book an Appointment</h2>

            <div id="schedule-info" class="schedule-banner" style="display:none;"></div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="book_appointment.php">
                <div class="mb-3">
                    <label>Select Doctor</label>
                    <select name="doctor_id" class="form-control" required>
                        <option value="" disabled selected>Choose a doctor</option>
                        <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                            <option value="<?php echo $doctor["user_id"]; ?>"><?php echo $doctor["full_name"]; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Appointment Date</label>
                    <input type="date" name="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-3">
                    <label>Appointment Time</label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Reason for Appointment</label>
                    <select name="reason" class="form-control" required>
                        <option value="Routine Check-up">Routine Check-up</option>
                        <option value="Follow-up">Follow-up</option>
                        <option value="New Symptoms">New Symptoms</option>
                        <option value="Chronic Condition">Chronic Condition</option>
                        <option value="Other">Other (Specify Below)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Additional Notes (Optional)</label>
                    <textarea name="additional_notes" class="form-control" rows="3" placeholder="Describe your symptoms or special requests"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Book Appointment</button>
            </form>
        </div>
    </div>

    <?php include "../partials/footer.php"; ?>

    <script>
        // embed schedule/unavailability data for frontend
        const schedules = <?php echo json_encode($schedules); ?>;
        const unavailability = <?php echo json_encode($unavailability); ?>;

        const doctorSelect = document.querySelector('select[name="doctor_id"]');
        const dateInput = document.querySelector('input[name="appointment_date"]');
        const timeInput = document.querySelector('input[name="appointment_time"]');
        const infoDiv = document.getElementById('schedule-info');

        function formatSchedule(docId) {
            if (!schedules[docId]) return 'No schedule defined for this doctor.';
            const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            return schedules[docId].map(s => `${days[s.day_of_week]} ${s.start_time.slice(0,5)}–${s.end_time.slice(0,5)}`).join(', ');
        }

        function updateInfo() {
            const docId = doctorSelect.value;
            if (!docId) {
                infoDiv.textContent = '';
                infoDiv.style.display = 'none';
                timeInput.min = '';
                timeInput.max = '';
                return;
            }
            let text = '<strong>Doctor availability:</strong> Working hours: ' + formatSchedule(docId);
            const date = dateInput.value;
            const time = timeInput.value;

            // adjust time picker bounds according to schedule of the day
            if (date && schedules[docId]) {
                const dow = new Date(date).getDay();
                const daySchedules = schedules[docId].filter(s => s.day_of_week == dow).sort((a, b) => a.start_time.localeCompare(b.start_time));
                if (daySchedules.length) {
                    timeInput.min = daySchedules[0].start_time;
                    timeInput.max = daySchedules[daySchedules.length - 1].end_time;
                    text += `<br><small>Selected day hours: ${daySchedules.map(s => `${s.start_time.slice(0,5)}-${s.end_time.slice(0,5)}`).join(', ')}</small>`;
                } else {
                    timeInput.min = '';
                    timeInput.max = '';
                    text += '<br><small class="text-danger">This doctor does not work on the selected date.</small>';
                }
            }

            if (date && time && unavailability[docId]) {
                let conflicting = unavailability[docId].some(u => {
                    return u.date === date && !(u.end_time <= time || u.start_time >= time);
                });
                if (conflicting) {
                    text += '<br><small class="text-danger">Doctor is marked unavailable at the selected time.</small>';
                }
            }
            infoDiv.innerHTML = text;
            infoDiv.style.display = 'block';
        }

        doctorSelect.addEventListener('change', updateInfo);
        dateInput.addEventListener('change', updateInfo);
        timeInput.addEventListener('change', updateInfo);
    </script>
</body>
</html>
