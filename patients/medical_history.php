<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$current_page = "medical_history";

$patient_id = $user_id;

// Fetch past medical records
$query = "SELECT m.record_id, m.diagnosis, m.prescription, m.notes, a.appointment_date, u.full_name AS doctor_name
          FROM medical_records m
          JOIN appointments a ON m.appointment_id = a.appointment_id
          JOIN users u ON a.doctor_id = u.user_id AND u.role = 'doctor'
          WHERE a.patient_id = ? AND a.status = 'completed'
          ORDER BY a.appointment_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$records = $stmt->get_result();

// Lab results across all of this patient's appointments
$lab_stmt = $conn->prepare("
    SELECT lr.test_name, lr.result_value, lr.normal_range, lr.created_at, a.appointment_date
    FROM lab_results lr
    JOIN appointments a ON lr.appointment_id = a.appointment_id
    WHERE a.patient_id = ?
    ORDER BY lr.created_at DESC
");
$lab_stmt->bind_param("i", $patient_id);
$lab_stmt->execute();
$lab_results = $lab_stmt->get_result();

// Dispensed medication across all of this patient's appointments
$pharm_stmt = $conn->prepare("
    SELECT pd.medication_name, pd.dosage, pd.quantity, pd.dispensed_at, a.appointment_date
    FROM pharmacy_dispenses pd
    JOIN appointments a ON pd.appointment_id = a.appointment_id
    WHERE a.patient_id = ?
    ORDER BY pd.dispensed_at DESC
");
$pharm_stmt->bind_param("i", $patient_id);
$pharm_stmt->execute();
$dispenses = $pharm_stmt->get_result();

$total_records = $records->num_rows;
$total_labs = $lab_results->num_rows;
$total_dispenses = $dispenses->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 22px; }
        @media (max-width: 700px) { .stat-row { grid-template-columns: 1fr; } }
        .stat-tile { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px 20px; display: flex; align-items: center; gap: 14px; }
        .stat-tile .stat-num { font-size: 1.5rem; font-weight: 800; color: var(--navy); line-height: 1; }
        .stat-tile .stat-lbl { color: var(--muted); font-size: .76rem; margin-top: 4px; }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Medical History</div>
            <div class="ab-subgreeting">Everything on file from your completed visits.</div>
        </div>
        <div class="ab-topbar-right">
            <a href="book_appointment.php" class="ab-btn ab-btn-primary"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION["full_name"] ?? "P", 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION["full_name"] ?? "") ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">Medical History</div>
        <div class="ab-page-sub">Diagnoses, lab results, and medication dispensed across every completed visit.</div>

        <div class="stat-row">
            <div class="stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-notes-medical"></i></div>
                <div><div class="stat-num"><?= $total_records ?></div><div class="stat-lbl">Medical Records</div></div>
            </div>
            <div class="stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-flask"></i></div>
                <div><div class="stat-num"><?= $total_labs ?></div><div class="stat-lbl">Lab Results</div></div>
            </div>
            <div class="stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-pills"></i></div>
                <div><div class="stat-num"><?= $total_dispenses ?></div><div class="stat-lbl">Medications Dispensed</div></div>
            </div>
        </div>

        <div class="ab-card" style="margin-bottom:22px">
            <div class="ab-card-title"><i class="fas fa-notes-medical"></i> Medical Records</div>
            <?php if ($total_records > 0): ?>
                <div style="margin-top:10px">
                <?php while ($record = $records->fetch_assoc()): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-file-medical"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($record["diagnosis"]) ?></span>
                            </div>
                            <div class="alr-meta">Dr. <?= htmlspecialchars($record["doctor_name"]) ?> &middot; <?= date("M j, Y", strtotime($record["appointment_date"])) ?></div>
                            <?php if (!empty($record["prescription"])): ?>
                                <div class="alr-detail"><span class="alr-detail-label">Prescription:</span> <?= htmlspecialchars($record["prescription"]) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($record["notes"])): ?>
                                <div class="alr-detail"><span class="alr-detail-label">Notes:</span> <?= htmlspecialchars($record["notes"]) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin-top:10px">No medical records available yet.</p>
            <?php endif; ?>
        </div>

        <div class="ab-card" style="margin-bottom:22px">
            <div class="ab-card-title"><i class="fas fa-flask"></i> Lab Results</div>
            <?php if ($total_labs > 0): ?>
                <div style="margin-top:10px">
                <?php while ($lr = $lab_results->fetch_assoc()): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-vial"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($lr["test_name"]) ?></span>
                                <span class="ab-pill ab-pill-neutral"><?= htmlspecialchars($lr["result_value"]) ?></span>
                            </div>
                            <div class="alr-meta">
                                <?= date("M j, Y", strtotime($lr["appointment_date"])) ?>
                                <?php if (!empty($lr["normal_range"])): ?>
                                    &middot; Normal range: <?= htmlspecialchars($lr["normal_range"]) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin-top:10px">No lab results available yet.</p>
            <?php endif; ?>
        </div>

        <div class="ab-card">
            <div class="ab-card-title"><i class="fas fa-pills"></i> Dispensed Medication</div>
            <?php if ($total_dispenses > 0): ?>
                <div style="margin-top:10px">
                <?php while ($pd = $dispenses->fetch_assoc()): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-prescription-bottle-medical"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($pd["medication_name"]) ?></span>
                                <?php if (!empty($pd["dosage"])): ?><span class="ab-pill ab-pill-neutral"><?= htmlspecialchars($pd["dosage"]) ?></span><?php endif; ?>
                                <?php if (!empty($pd["quantity"])): ?><span class="ab-pill ab-pill-neutral"><?= htmlspecialchars($pd["quantity"]) ?></span><?php endif; ?>
                            </div>
                            <div class="alr-meta">Dispensed <?= date("M j, Y", strtotime($pd["dispensed_at"])) ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin-top:10px">No dispensed medication on record.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
