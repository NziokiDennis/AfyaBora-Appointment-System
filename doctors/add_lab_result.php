<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$appointment_id = (int)($_GET["appointment_id"] ?? 0);
$success = "";
$error = "";
$appointment = null;
$current_page = "appointments";
$doctor_id = $_SESSION["user_id"];

if ($appointment_id) {
    $query = "SELECT a.appointment_date, a.payment_status, a.reason, u.full_name AS patient_name
              FROM appointments a
              JOIN users u ON a.patient_id = u.user_id
              WHERE a.appointment_id = ? AND a.doctor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $appointment_id, $doctor_id);
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
    <title>Lab Results — AfyaBora</title>
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
            <div class="ab-greeting">Lab Results</div>
            <div class="ab-subgreeting"><?= $appointment ? htmlspecialchars($appointment['patient_name']) : 'Record patient lab results' ?></div>
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
        <div class="ab-page-title"><i class="fas fa-flask"></i> Lab Results</div>
        <div class="ab-page-sub">Add and review lab results for this appointment.</div>

        <div class="ab-card" style="margin-bottom:18px">
            <?php if ($appointment): ?>
                <div class="ab-info-banner">
                    <strong><?= htmlspecialchars($appointment["patient_name"]) ?></strong><br>
                    <?= date("l, F j, Y", strtotime($appointment["appointment_date"])) ?>
                    <br><span class="ab-pill ab-pill-green" style="margin-top:6px"><i class="fas fa-check"></i> Payment Confirmed</span>
                </div>
            <?php elseif ($error): ?>
                <div class="ab-alert ab-alert-danger"><i class="fas fa-lock"></i> <?= htmlspecialchars($error) ?></div>
                <a href="appointments.php" class="ab-btn ab-btn-primary">Back to Appointments</a>
            <?php else: ?>
                <div class="ab-alert ab-alert-danger">Invalid appointment.</div>
                <a href="appointments.php" class="ab-btn ab-btn-primary">Back to Appointments</a>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="ab-alert ab-alert-success" style="margin-top:14px"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
            <?php elseif ($error && $appointment): ?>
                <div class="ab-alert ab-alert-danger" style="margin-top:14px"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        </div>

        <?php if ($appointment): ?>
            <?php if ($lab_results): ?>
            <div class="ab-card" style="margin-bottom:18px">
                <div class="ab-card-title"><i class="fas fa-vial"></i> Recorded Results</div>
                <?php foreach ($lab_results as $lr): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-flask"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($lr['test_name']) ?></span>
                                <span class="ab-pill ab-pill-neutral"><?= htmlspecialchars($lr['result_value']) ?></span>
                                <?php if ($lr['normal_range']): ?><span class="ab-pill ab-pill-neutral">normal: <?= htmlspecialchars($lr['normal_range']) ?></span><?php endif; ?>
                            </div>
                            <div class="alr-meta"><?= date("M j, Y g:i A", strtotime($lr['created_at'])) ?></div>
                            <?php if ($lr['notes']): ?><div class="alr-detail"><?= htmlspecialchars($lr['notes']) ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="ab-card">
                <div class="ab-card-title"><i class="fas fa-plus"></i> Add a Lab Result</div>
                <form method="POST" action="add_lab_result.php?appointment_id=<?= $appointment_id ?>">
                    <div class="ab-form-group">
                        <label class="ab-label">Test Name <span class="req">*</span></label>
                        <input type="text" name="test_name" class="ab-input" placeholder="e.g. Full Blood Count" required>
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Result <span class="req">*</span></label>
                        <input type="text" name="result_value" class="ab-input" placeholder="e.g. 13.5 g/dL" required>
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Normal Range (Optional)</label>
                        <input type="text" name="normal_range" class="ab-input" placeholder="e.g. 12.0–15.5 g/dL">
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Notes (Optional)</label>
                        <textarea name="notes" class="ab-input" rows="2"></textarea>
                    </div>
                    <button type="submit" class="ab-btn ab-btn-primary" style="width:100%;justify-content:center"><i class="fas fa-floppy-disk"></i> Save Lab Result</button>
                </form>
            </div>
        <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
