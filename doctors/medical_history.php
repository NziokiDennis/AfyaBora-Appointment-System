<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$doctor_id = $_SESSION["user_id"];
$current_page = "medical_history";

$query = "SELECT a.appointment_id, a.appointment_date, u.full_name AS patient_name,
                 m.diagnosis, m.prescription, m.notes
          FROM appointments a
          JOIN users u ON a.patient_id = u.user_id
          JOIN medical_records m ON a.appointment_id = m.appointment_id
          WHERE a.doctor_id = ? AND a.status = 'completed'
          ORDER BY a.appointment_date DESC";
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
    <title>Medical Records — AfyaBora</title>
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
            <div class="ab-greeting">Medical Records</div>
            <div class="ab-subgreeting"><?= $total ?> completed record<?= $total === 1 ? '' : 's' ?></div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">My Medical Records</div>
        <div class="ab-page-sub">Diagnoses and prescriptions recorded for completed appointments.</div>

        <div class="ab-card">
            <?php if ($total > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-file-medical"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($row['patient_name']) ?></span>
                                <span class="alr-meta"><?= date('M j, Y', strtotime($row['appointment_date'])) ?></span>
                            </div>
                            <?php if (!empty($row['diagnosis'])): ?><div class="alr-detail"><span class="alr-detail-label">Diagnosis:</span> <?= htmlspecialchars($row['diagnosis']) ?></div><?php endif; ?>
                            <?php if (!empty($row['prescription'])): ?><div class="alr-detail"><span class="alr-detail-label">Prescription:</span> <?= htmlspecialchars($row['prescription']) ?></div><?php endif; ?>
                            <?php if (!empty($row['notes'])): ?><div class="alr-detail"><span class="alr-detail-label">Notes:</span> <?= htmlspecialchars($row['notes']) ?></div><?php endif; ?>
                        </div>
                        <div class="alr-trailing">
                            <a href="add_medical_record.php?appointment_id=<?= (int)$row['appointment_id'] ?>" class="ab-btn ab-btn-secondary ab-btn-sm"><i class="fas fa-pen"></i></a>
                            <a href="delete_medical_record.php?appointment_id=<?= (int)$row['appointment_id'] ?>" class="ab-btn ab-btn-danger ab-btn-sm" onclick="return confirm('Delete this record?');"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin:6px 0 0">No medical records found.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
