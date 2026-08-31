<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$doctor_id = $_SESSION["user_id"];
$current_page = "appointments";

$spec_stmt = $conn->prepare("SELECT specialization FROM users WHERE user_id = ?");
$spec_stmt->bind_param("i", $doctor_id);
$spec_stmt->execute();
$doctor_specialization = $spec_stmt->get_result()->fetch_assoc()["specialization"] ?? null;

$query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.payment_status,
                 a.payment_amount, a.payment_date, u.full_name AS patient_name
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = ? AND a.status = 'scheduled'
          ORDER BY a.appointment_date, a.appointment_time";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Appointments — AfyaBora</title>
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
            <div class="ab-greeting">Scheduled Appointments</div>
            <div class="ab-subgreeting"><?= $total ?> upcoming</div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">Scheduled Appointments</div>
        <div class="ab-page-sub"><?= $doctor_specialization ? htmlspecialchars($doctor_specialization) : 'Your upcoming patient appointments.' ?></div>

        <div class="ab-card">
            <?php if ($total > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-user"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($row['patient_name']) ?></span>
                                <?php if ($row['payment_status'] == 'paid'): ?>
                                    <span class="ab-pill ab-pill-green"><i class="fas fa-check"></i> Paid</span>
                                <?php else: ?>
                                    <span class="ab-pill ab-pill-amber"><i class="fas fa-triangle-exclamation"></i> Payment Pending</span>
                                <?php endif; ?>
                            </div>
                            <div class="alr-meta">
                                <?= date('D, M j, Y', strtotime($row['appointment_date'])) ?> · <?= date('g:i A', strtotime($row['appointment_time'])) ?>
                                · KSh <?= number_format($row['payment_amount'], 2) ?>
                                <?= $row['payment_date'] ? ' · paid ' . date('M j, Y', strtotime($row['payment_date'])) : '' ?>
                            </div>
                        </div>
                        <div class="alr-trailing">
                            <?php if ($row['payment_status'] == 'paid'): ?>
                                <a href="add_medical_record.php?appointment_id=<?= (int)$row['appointment_id'] ?>" class="ab-btn ab-btn-primary ab-btn-sm"><i class="fas fa-file-medical"></i> Record</a>
                                <a href="add_lab_result.php?appointment_id=<?= (int)$row['appointment_id'] ?>" class="ab-btn ab-btn-secondary ab-btn-sm"><i class="fas fa-flask"></i> Lab</a>
                            <?php else: ?>
                                <span class="ab-btn ab-btn-sm" style="background:var(--canvas);color:var(--muted);cursor:not-allowed" title="Payment required"><i class="fas fa-lock"></i> Locked</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin:6px 0 0">No scheduled appointments found.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
