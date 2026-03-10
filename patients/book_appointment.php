<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$success = "";
$error = "";

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

// helper for server-side availability check
function doctorAvailable($conn, $doctor_id, $date, $time) {
    // check weekly schedule
    $dow = date('w', strtotime($date));
    $stmt = $conn->prepare("SELECT start_time, end_time FROM doctor_schedules WHERE doctor_id=? AND day_of_week=?");
    $stmt->bind_param("ii", $doctor_id, $dow);
    $stmt->execute();
    $result = $stmt->get_result();
    $sched = $result->fetch_assoc();
    if (!$sched) {
        return false; // no working hours defined
    }
    if ($time < $sched['start_time'] || $time >= $sched['end_time']) {
        return false; // outside declared hours
    }

    // check specific unavailability
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM doctor_unavailability WHERE doctor_id=? AND date=? AND NOT (end_time <= ? OR start_time >= ?)");
    $stmt->bind_param("isss", $doctor_id, $date, $time, $time);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'];
    if ($cnt > 0) {
        return false;
    }

    // check existing appointment conflict (only scheduled)
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status='scheduled'");
    $stmt->bind_param("iss", $doctor_id, $date, $time);
    $stmt->execute();
    $cnt2 = $stmt->get_result()->fetch_assoc()['cnt'];
    if ($cnt2 > 0) {
        return false;
    }

    return true;
}

// Handle appointment booking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = $_POST["doctor_id"];
    $appointment_date = $_POST["appointment_date"];
    $appointment_time = $_POST["appointment_time"];
    $reason = $_POST["reason"];
    $additional_notes = trim($_POST["additional_notes"]);

    // Validate selected date (must not be in the past)
    $current_date = date("Y-m-d");
    if ($appointment_date < $current_date) {
        $error = "You cannot book an appointment for a past date.";
    } elseif (!doctorAvailable($conn, $doctor_id, $appointment_date, $appointment_time)) {
        $error = "Doctor is not available at the requested date/time.";
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
        $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, additional_notes) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iissss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $additional_notes);

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
    </style>
</head>
<body>

    <?php include "navbar.php"; ?>

    <div class="container">
        <div class="appointment-card">
            <h2 class="text-center">Book an Appointment</h2>

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
                <div id="schedule-info" class="mb-3 text-secondary" style="font-size:0.9rem;"></div>
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
                timeInput.min = '';
                timeInput.max = '';
                return;
            }
            let text = 'Working hours: ' + formatSchedule(docId);
            const date = dateInput.value;
            const time = timeInput.value;

            // adjust time picker bounds according to schedule of the day
            if (date && schedules[docId]) {
                const dow = new Date(date).getDay();
                const daySched = schedules[docId].find(s => s.day_of_week == dow);
                if (daySched) {
                    timeInput.min = daySched.start_time;
                    timeInput.max = daySched.end_time;
                } else {
                    timeInput.min = '';
                    timeInput.max = '';
                }
            }

            if (date && time && unavailability[docId]) {
                let conflicting = unavailability[docId].some(u => {
                    return u.date === date && !(u.end_time <= time || u.start_time >= time);
                });
                if (conflicting) {
                    text += ' (UNAVAILABLE at selected time)';
                }
            }
            infoDiv.textContent = text;
        }

        doctorSelect.addEventListener('change', updateInfo);
        dateInput.addEventListener('change', updateInfo);
        timeInput.addEventListener('change', updateInfo);
    </script>
</body>
</html>
