<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$appointment_id = $_GET["appointment_id"] ?? null;
$success = "";
$error = "";
$appointment = null;
$existing_record = null;

// Fetch patient name, appointment date and payment status
if ($appointment_id) {
    $query = "SELECT a.appointment_date, a.payment_status, a.payment_amount, a.reason, a.additional_notes, u.full_name AS patient_name
              FROM appointments a
              JOIN patients p ON a.patient_id = p.patient_id
              JOIN users u ON p.user_id = u.user_id
              WHERE a.appointment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointment_id);
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
                // Mark appointment as completed
                $conn->query("UPDATE appointments SET status = 'completed' WHERE appointment_id = $appointment_id");
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
    <title>Add Medical Record</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .record-card {
            max-width: 600px;
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
        <div class="record-card">
            <h2 class="text-center">
                <?php echo $appointment ? 'Medical Record for ' . htmlspecialchars($appointment['reason'] ?? 'Appointment') : 'Add Medical Record'; ?>
            </h2>

            <?php if ($appointment): ?>
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($appointment["patient_name"]); ?></p>
                <p><strong>Appointment Date:</strong> <?php echo date("F j, Y", strtotime($appointment["appointment_date"])); ?></p>
                <p><strong>Reason:</strong> <?php echo htmlspecialchars($appointment["reason"] ?? ''); ?></p>
                <?php if (!empty($appointment['additional_notes'])): ?>
                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($appointment['additional_notes']); ?></p>
                <?php endif; ?>
                <p><span class="badge bg-success"><i class="fas fa-check-circle"></i> Payment Confirmed</span></p>
            <?php elseif ($error): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-lock"></i> <?php echo $error; ?>
                </div>
                <a href="appointments.php" class="btn btn-primary">Back to Appointments</a>
            <?php else: ?>
                <div class="alert alert-danger">Invalid appointment or already processed.</div>
                <a href="appointments.php" class="btn btn-primary">Back to Appointments</a>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($appointment): ?>
                <form method="POST" action="add_medical_record.php?appointment_id=<?php echo $appointment_id; ?>">
                    <div class="mb-3">
                        <label>Diagnosis</label>
                        <textarea name="diagnosis" class="form-control" rows="3" required><?php echo htmlspecialchars($existing_record['diagnosis'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Prescription</label>
                        <textarea name="prescription" class="form-control" rows="3" required><?php echo htmlspecialchars($existing_record['prescription'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Additional Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($existing_record['notes'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo $existing_record ? 'Update' : 'Save'; ?> Medical Record</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php include "../partials/footer.php"; ?>
</body>
</html>
