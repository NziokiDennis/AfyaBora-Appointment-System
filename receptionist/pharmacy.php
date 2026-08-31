<?php
require_once "../config/auth.php";
checkRole("receptionist");
require_once "../config/db.php";

$message = "";
$error = "";
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
    JOIN users d ON a.doctor_id = d.user_id
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
    <title>Pharmacy — Dispense Medication</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 40px; }
        .card-box { background: white; border-radius: 12px; padding: 22px; box-shadow: 0 0 10px rgba(0,0,0,.08); margin-bottom: 24px; }
    </style>
</head>
<body>
    <?php include "navbar.php"; ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="fas fa-pills"></i> Pharmacy — Dispense Medication</h2>
            <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card-box">
            <h5>Record a Dispense</h5>
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label>Appointment (Patient — Doctor — Date)</label>
                    <select name="appointment_id" class="form-control" required>
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
                <div class="col-md-6">
                    <label>Medication Name</label>
                    <input type="text" name="medication_name" class="form-control" placeholder="e.g. Amoxicillin" required>
                </div>
                <div class="col-md-4">
                    <label>Dosage</label>
                    <input type="text" name="dosage" class="form-control" placeholder="e.g. 500mg twice daily">
                </div>
                <div class="col-md-4">
                    <label>Quantity</label>
                    <input type="text" name="quantity" class="form-control" placeholder="e.g. 14 tablets">
                </div>
                <div class="col-md-4">
                    <label>Notes (Optional)</label>
                    <input type="text" name="notes" class="form-control" placeholder="Any dispensing notes">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Record Dispense</button>
                </div>
            </form>
        </div>

        <div class="card-box">
            <h5>Recent Dispensing History</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Quantity</th>
                            <th>Dispensed By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history && $history->num_rows > 0): ?>
                            <?php while ($row = $history->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($row['medication_name']) ?></td>
                                    <td><?= htmlspecialchars($row['dosage'] ?: '—') ?></td>
                                    <td><?= htmlspecialchars($row['quantity'] ?: '—') ?></td>
                                    <td><?= htmlspecialchars($row['dispensed_by_name'] ?? 'N/A') ?></td>
                                    <td><?= date("M j, Y g:i A", strtotime($row['dispensed_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-3">No dispensing records yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include "../partials/footer.php"; ?>
</body>
</html>
