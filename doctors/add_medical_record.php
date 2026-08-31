<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

// SQL-injection prevention: appointment_id comes straight from the URL query string, so it is
// cast to an integer at the point of entry, in addition to being bound as a typed parameter
// ("i") everywhere it is later used in a query below.
$appointment_id = isset($_GET["appointment_id"]) ? (int)$_GET["appointment_id"] : null;
$success = "";
$error = "";
$appointment = null;
$existing_record = null;
$current_page = "appointments";

$doctor_id = $_SESSION["user_id"];
$spec_stmt = $conn->prepare("SELECT specialization FROM users WHERE user_id = ?");
$spec_stmt->bind_param("i", $doctor_id);
$spec_stmt->execute();
$doctor_specialization = $spec_stmt->get_result()->fetch_assoc()["specialization"] ?? null;

// Fetch patient name, appointment date and payment status
if ($appointment_id) {
    $query = "SELECT a.appointment_date, a.payment_status, a.payment_amount, a.reason, a.additional_notes, u.full_name AS patient_name
              FROM appointments a
              JOIN users u ON a.patient_id = u.user_id
              WHERE a.appointment_id = ? AND a.doctor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $appointment_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    
    // Check if payment is completed
    if ($appointment && $appointment['payment_status'] != 'paid') {
        $error = "Cannot add medical record. Patient has not completed payment for this appointment.";
        $appointment = null; // Prevent form from showing
    }
    // load existing record if any
    if ($appointment) {
        $rec_stmt = $conn->prepare("SELECT diagnosis, prescription, notes FROM medical_records WHERE appointment_id = ?");
        $rec_stmt->bind_param("i", $appointment_id);
        $rec_stmt->execute();
        $rec_res = $rec_stmt->get_result();
        $existing_record = $rec_res->fetch_assoc();
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && $appointment) {
    $diagnosis = trim($_POST["diagnosis"]);
    $prescription = trim($_POST["prescription"]);
    $notes = trim($_POST["notes"]);

    if (empty($diagnosis) || empty($prescription)) {
        $error = "Diagnosis and Prescription are required.";
    } else {
        if ($existing_record) {
            // update existing record
            $update_query = "UPDATE medical_records SET diagnosis=?, prescription=?, notes=? WHERE appointment_id=?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssi", $diagnosis, $prescription, $notes, $appointment_id);
            if ($stmt->execute()) {
                $success = "Medical record updated.";
            } else {
                $error = "Error updating medical record.";
            }
        } else {
            // Insert medical record
            $insert_query = "INSERT INTO medical_records (appointment_id, diagnosis, prescription, notes) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("isss", $appointment_id, $diagnosis, $prescription, $notes);

            if ($stmt->execute()) {
                // Mark appointment as completed.
                // SQL-injection prevention: previously this ran $conn->query() with $appointment_id
                // concatenated directly into the SQL string. Because $appointment_id originated from
                // $_GET and was never cast to int, a request such as
                //   add_medical_record.php?appointment_id=5 OR 1=1
                // would have marked every appointment in the table as 'completed' in one call.
                // It is now a prepared statement with the value bound as an integer parameter,
                // so the input can only ever be compared as a number, never as SQL.
                $complete_stmt = $conn->prepare("UPDATE appointments SET status = 'completed' WHERE appointment_id = ?");
                $complete_stmt->bind_param("i", $appointment_id);
                $complete_stmt->execute();
                $success = "Medical record added and appointment marked as completed!";
            } else {
                $error = "Error saving medical record.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Record — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Medical Record</div>
            <div class="ab-subgreeting"><?= $doctor_specialization ? htmlspecialchars($doctor_specialization) : 'Doctor Portal' ?></div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-center-viewport">
        <div class="ab-page-title"><?= $appointment ? 'Medical Record' : 'Add Medical Record' ?></div>
        <div class="ab-page-sub"><?= $appointment ? htmlspecialchars($appointment['reason'] ?? 'Appointment') : 'Record diagnosis, prescription, and notes.' ?></div>

        <div class="ab-card">
            <?php if ($appointment): ?>
                <div class="ab-info-banner">
                    <strong><?= htmlspecialchars($appointment["patient_name"]) ?></strong><br>
                    <?= date("l, F j, Y", strtotime($appointment["appointment_date"])) ?>
                    <?php if (!empty($appointment['additional_notes'])): ?><br>Notes: <?= htmlspecialchars($appointment['additional_notes']) ?><?php endif; ?>
                    <br><span class="ab-pill ab-pill-green" style="margin-top:6px"><i class="fas fa-check"></i> Payment Confirmed</span>
                </div>
            <?php elseif ($error): ?>
                <div class="ab-alert ab-alert-danger"><i class="fas fa-lock"></i> <?= htmlspecialchars($error) ?></div>
                <a href="appointments.php" class="ab-btn ab-btn-primary">Back to Appointments</a>
            <?php else: ?>
                <div class="ab-alert ab-alert-danger">Invalid appointment or already processed.</div>
                <a href="appointments.php" class="ab-btn ab-btn-primary">Back to Appointments</a>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="ab-alert ab-alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
            <?php elseif ($error && $appointment): ?>
                <div class="ab-alert ab-alert-danger"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($appointment): ?>
                <form method="POST" action="add_medical_record.php?appointment_id=<?= $appointment_id ?>">
                    <div class="ab-form-group">
                        <label class="ab-label">Diagnosis <span class="req">*</span></label>
                        <textarea name="diagnosis" class="ab-input" rows="3" required><?= htmlspecialchars($existing_record['diagnosis'] ?? '') ?></textarea>
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Prescription <span class="req">*</span></label>
                        <textarea name="prescription" class="ab-input" rows="3" required><?= htmlspecialchars($existing_record['prescription'] ?? '') ?></textarea>
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Additional Notes (Optional)</label>
                        <textarea name="notes" class="ab-input" rows="3"><?= htmlspecialchars($existing_record['notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="ab-btn ab-btn-primary" style="width:100%;justify-content:center"><i class="fas fa-floppy-disk"></i> <?= $existing_record ? 'Update' : 'Save' ?> Medical Record</button>
                </form>
            <?php endif; ?>
        </div>
        </div>
    </main>
</div>

</body>
</html>
