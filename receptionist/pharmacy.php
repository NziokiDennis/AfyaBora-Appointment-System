<?php
require_once "../config/auth.php";
checkRole("receptionist");
require_once "../config/db.php";

$message = "";
$error = "";
$current_page = "pharmacy";
$receptionist_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointment_id  = (int)($_POST["appointment_id"] ?? 0);
    $medication_name = trim($_POST["medication_name"] ?? "");
    $dosage          = trim($_POST["dosage"] ?? "");
    $quantity        = trim($_POST["quantity"] ?? "");
    $notes           = trim($_POST["notes"] ?? "");

    if (!$appointment_id || $medication_name === "") {
        $error = "Please choose an appointment and enter a medication name.";
    } else {
        // Parameterised INSERT -- every value is bound, never concatenated into the SQL string.
        $stmt = $conn->prepare(
            "INSERT INTO pharmacy_dispenses (appointment_id, medication_name, dosage, quantity, dispensed_by, notes)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isssis", $appointment_id, $medication_name, $dosage, $quantity, $receptionist_id, $notes);
        if ($stmt->execute()) {
            $message = "Dispense recorded.";
        } else {
            $error = "Could not record dispense.";
        }
    }
}

// Appointments eligible for dispensing: paid, with a prescription already recorded by the doctor.
$eligible_sql = "
    SELECT a.appointment_id, a.appointment_date, mr.prescription,
           puser.full_name AS patient_name, d.full_name AS doctor_name
    FROM appointments a
    JOIN medical_records mr ON mr.appointment_id = a.appointment_id
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users puser ON p.user_id = puser.user_id
    JOIN users d ON a.doctor_id = d.user_id AND d.role = 'doctor'
    WHERE a.payment_status = 'paid'
    ORDER BY a.appointment_date DESC
    LIMIT 50
";
$eligible = $conn->query($eligible_sql);

// Dispensing history (read, joined for display context)
$history_sql = "
    SELECT pd.medication_name, pd.dosage, pd.quantity, pd.dispensed_at, pd.notes,
           puser.full_name AS patient_name, u.full_name AS dispensed_by_name
    FROM pharmacy_dispenses pd
    JOIN appointments a ON pd.appointment_id = a.appointment_id
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users puser ON p.user_id = puser.user_id
    LEFT JOIN users u ON pd.dispensed_by = u.user_id
    ORDER BY pd.dispensed_at DESC
    LIMIT 30
";
$history = $conn->query($history_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy — AfyaBora</title>
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
            <div class="ab-greeting">Pharmacy</div>
            <div class="ab-subgreeting">Dispense medication for paid appointments.</div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'R', 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title"><i class="fas fa-pills"></i> Dispense Medication</div>
        <div class="ab-page-sub">Record medication dispensed against a doctor's prescription.</div>

        <?php if ($message): ?><div class="ab-alert ab-alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="ab-alert ab-alert-warning"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="ab-card" style="margin-bottom:20px">
            <div class="ab-card-title"><i class="fas fa-plus"></i> Record a Dispense</div>
            <form method="POST">
                <div class="ab-form-group">
                    <label class="ab-label">Appointment (Patient — Doctor — Date) <span class="req">*</span></label>
                    <select name="appointment_id" class="ab-input" required>
                        <option value="" disabled selected>Select an appointment</option>
                        <?php if ($eligible): while ($row = $eligible->fetch_assoc()): ?>
                            <option value="<?= (int)$row['appointment_id'] ?>">
                                <?= htmlspecialchars($row['patient_name']) ?> — Dr. <?= htmlspecialchars($row['doctor_name']) ?> —
                                <?= date("M j, Y", strtotime($row['appointment_date'])) ?>
                                (Rx: <?= htmlspecialchars(mb_strimwidth($row['prescription'], 0, 40, '…')) ?>)
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="ab-form-group">
                    <label class="ab-label">Medication Name <span class="req">*</span></label>
                    <input type="text" name="medication_name" class="ab-input" placeholder="e.g. Amoxicillin" required>
                </div>
                <div class="ab-form-row">
                    <div class="ab-form-group">
                        <label class="ab-label">Dosage</label>
                        <input type="text" name="dosage" class="ab-input" placeholder="e.g. 500mg twice daily">
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Quantity</label>
                        <input type="text" name="quantity" class="ab-input" placeholder="e.g. 14 tablets">
                    </div>
                </div>
                <div class="ab-form-group">
                    <label class="ab-label">Notes (Optional)</label>
                    <input type="text" name="notes" class="ab-input" placeholder="Any dispensing notes">
                </div>
                <button type="submit" class="ab-btn ab-btn-primary" style="width:100%;justify-content:center"><i class="fas fa-check"></i> Record Dispense</button>
            </form>
        </div>

        <div class="ab-card">
            <div class="ab-card-title"><i class="fas fa-clock-rotate-left"></i> Recent Dispensing History</div>
            <?php if ($history && $history->num_rows > 0): ?>
                <?php while ($row = $history->fetch_assoc()): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-pills"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($row['patient_name']) ?></span>
                                <span class="ab-pill ab-pill-neutral"><?= htmlspecialchars($row['medication_name']) ?></span>
                                <?php if ($row['dosage']): ?><span class="ab-pill ab-pill-neutral"><?= htmlspecialchars($row['dosage']) ?></span><?php endif; ?>
                                <?php if ($row['quantity']): ?><span class="ab-pill ab-pill-neutral"><?= htmlspecialchars($row['quantity']) ?></span><?php endif; ?>
                            </div>
                            <div class="alr-meta">
                                Dispensed by <?= htmlspecialchars($row['dispensed_by_name'] ?? 'N/A') ?> · <?= date("M j, Y g:i A", strtotime($row['dispensed_at'])) ?>
                            </div>
                            <?php if ($row['notes']): ?><div class="alr-detail"><?= htmlspecialchars($row['notes']) ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin:6px 0 0">No dispensing records yet.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
