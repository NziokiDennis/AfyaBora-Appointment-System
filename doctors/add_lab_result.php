<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$appointment_id = (int)($_GET["appointment_id"] ?? 0);
$success = "";
$error = "";
$appointment = null;

if ($appointment_id) {
    $query = "SELECT a.appointment_date, a.payment_status, a.reason, u.full_name AS patient_name
              FROM appointments a
              JOIN patients p ON a.patient_id = p.patient_id
              JOIN users u ON p.user_id = u.user_id
              WHERE a.appointment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();

    if ($appointment && $appointment['payment_status'] !== 'paid') {
        $error = "Cannot add a lab result. Patient has not completed payment for this appointment.";
        $appointment = null;
    }
}

// Handle form submission -- parameterised INSERT, no string interpolation of user input.
if ($_SERVER["REQUEST_METHOD"] === "POST" && $appointment) {
    $test_name    = trim($_POST["test_name"] ?? "");
    $result_value = trim($_POST["result_value"] ?? "");
    $normal_range = trim($_POST["normal_range"] ?? "");
    $notes        = trim($_POST["notes"] ?? "");
    $doctor_id    = $_SESSION["user_id"];

    if (empty($test_name) || empty($result_value)) {
        $error = "Test name and result are required.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO lab_results (appointment_id, test_name, result_value, normal_range, notes, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issssi", $appointment_id, $test_name, $result_value, $normal_range, $notes, $doctor_id);
        if ($stmt->execute()) {
            $success = "Lab result added.";
        } else {
            $error = "Error saving lab result.";
        }
    }
}

// List existing lab results for this appointment (read, parameterised)
$lab_results = [];
if ($appointment_id) {
    $lr_stmt = $conn->prepare("SELECT test_name, result_value, normal_range, notes, created_at FROM lab_results WHERE appointment_id = ? ORDER BY created_at DESC");
    $lr_stmt->bind_param("i", $appointment_id);
    $lr_stmt->execute();
    $lab_results = $lr_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lab Result</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .record-card {
            max-width: 640px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-primary { width: 100%; }
        .lab-row { border-bottom: 1px solid #eee; padding: 10px 0; }
    </style>
</head>
<body>

    <?php include "navbar.php"; ?>

    <div class="container">
        <div class="record-card">
            <h2 class="text-center"><i class="fas fa-flask"></i> Lab Results</h2>

            <?php if ($appointment): ?>
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($appointment["patient_name"]); ?></p>
                <p><strong>Appointment Date:</strong> <?php echo date("F j, Y", strtotime($appointment["appointment_date"])); ?></p>
                <p><span class="badge bg-success"><i class="fas fa-check-circle"></i> Payment Confirmed</span></p>
            <?php elseif ($error): ?>
                <div class="alert alert-warning"><i class="fas fa-lock"></i> <?php echo htmlspecialchars($error); ?></div>
                <a href="appointments.php" class="btn btn-primary">Back to Appointments</a>
            <?php else: ?>
                <div class="alert alert-danger">Invalid appointment.</div>
                <a href="appointments.php" class="btn btn-primary">Back to Appointments</a>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php elseif ($error && $appointment): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($appointment): ?>
                <?php if ($lab_results): ?>
                    <h5 class="mt-4">Recorded Results</h5>
                    <?php foreach ($lab_results as $lr): ?>
                        <div class="lab-row">
                            <strong><?php echo htmlspecialchars($lr['test_name']); ?>:</strong>
                            <?php echo htmlspecialchars($lr['result_value']); ?>
                            <?php if ($lr['normal_range']): ?>
                                <span class="text-muted">(normal: <?php echo htmlspecialchars($lr['normal_range']); ?>)</span>
                            <?php endif; ?>
                            <div class="small text-muted"><?php echo date("M j, Y g:i A", strtotime($lr['created_at'])); ?></div>
                            <?php if ($lr['notes']): ?><div class="small"><?php echo htmlspecialchars($lr['notes']); ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h5 class="mt-4">Add a Lab Result</h5>
                <form method="POST" action="add_lab_result.php?appointment_id=<?php echo $appointment_id; ?>">
                    <div class="mb-3">
                        <label>Test Name</label>
                        <input type="text" name="test_name" class="form-control" placeholder="e.g. Full Blood Count" required>
                    </div>
                    <div class="mb-3">
                        <label>Result</label>
                        <input type="text" name="result_value" class="form-control" placeholder="e.g. 13.5 g/dL" required>
                    </div>
                    <div class="mb-3">
                        <label>Normal Range (Optional)</label>
                        <input type="text" name="normal_range" class="form-control" placeholder="e.g. 12.0–15.5 g/dL">
                    </div>
                    <div class="mb-3">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Lab Result</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php include "../partials/footer.php"; ?>
</body>
</html>
