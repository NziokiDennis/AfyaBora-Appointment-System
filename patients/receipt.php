<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$appointment_id = (int)($_GET["appointment_id"] ?? 0);

if (!$appointment_id) {
    header("Location: dashboard.php?error=" . urlencode("Receipt not found."));
    exit;
}

$patientStmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$patientStmt->bind_param("i", $user_id);
$patientStmt->execute();
$patient = $patientStmt->get_result()->fetch_assoc();

if (!$patient) {
    header("Location: dashboard.php?error=" . urlencode("Patient record not found."));
    exit;
}

$stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.payment_status, a.payment_amount,
           a.payment_date, a.payment_method, a.payment_reference, a.reason,
           d.full_name AS doctor_name, d.specialization AS doctor_specialization, u.full_name AS patient_name, u.email, u.phone_number
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    JOIN users d ON a.doctor_id = d.user_id AND d.role = 'doctor'
    WHERE a.appointment_id = ? AND a.patient_id = ?
");
$stmt->bind_param("ii", $appointment_id, $patient["patient_id"]);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt || $receipt["payment_status"] !== "paid") {
    header("Location: dashboard.php?error=" . urlencode("Receipt is only available after payment confirmation."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --navy: #002d70; --navy2: #134589; --blue: #0b63c3; --blue2: #094f9e;
            --sky: #eef3fb; --canvas: #f5f6fa; --white: #ffffff; --border: #e8ebf0;
            --muted: #5b6169; --green: #1fae7a; --radius-sm: 8px; --radius-lg: 16px; --radius-pill: 999px;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--canvas); color: var(--navy); margin: 0; }
        .receipt-card {
            max-width: 760px;
            margin: 40px auto;
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,45,112,0.06);
            overflow: hidden;
        }
        .receipt-header { background: var(--navy); color: #fff; padding: 26px 28px; }
        .receipt-header h2 { margin: 0 0 4px; font-size: 1.3rem; font-weight: 800; }
        .receipt-header div { font-size: .88rem; color: rgba(255,255,255,.75); }
        .receipt-body { padding: 28px; }
        .receipt-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .receipt-top h5 { margin: 0 0 2px; font-size: 1rem; font-weight: 700; }
        .receipt-top .muted { color: var(--muted); font-size: .86rem; }
        .badge-confirmed {
            background: rgba(31,174,122,.12); color: var(--green); font-weight: 700;
            font-size: .85rem; padding: 5px 14px; border-radius: var(--radius-pill);
        }
        .receipt-row { display: flex; justify-content: space-between; gap: 20px; padding: 11px 0; border-bottom: 1px solid var(--border); font-size: .92rem; }
        .receipt-row:last-of-type { border-bottom: none; }
        .receipt-row strong { color: var(--muted); font-weight: 600; }
        .receipt-row span strong { color: var(--navy); }
        .ab-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: var(--radius-sm); font-size: .88rem; font-weight: 600; border: 1.5px solid transparent; cursor: pointer; text-decoration: none; }
        .ab-btn-primary { background: var(--navy); color: #fff; }
        .ab-btn-secondary { background: #fff; color: var(--blue); border-color: var(--blue); }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .receipt-card { box-shadow: none; margin: 0; max-width: 100%; border: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="receipt-header">
            <h2><i class="fas fa-heartbeat"></i> AfyaBora Payment Receipt</h2>
            <div>Official receipt for confirmed appointment payment</div>
        </div>
        <div class="receipt-body">
            <div class="receipt-top">
                <div>
                    <h5>Receipt No. <?= htmlspecialchars($receipt["payment_reference"]) ?></h5>
                    <div class="muted">Appointment #<?= (int)$receipt["appointment_id"] ?></div>
                </div>
                <span class="badge-confirmed"><i class="fas fa-check"></i> Confirmed</span>
            </div>

            <div class="receipt-row"><strong>Patient</strong><span><?= htmlspecialchars($receipt["patient_name"]) ?></span></div>
            <div class="receipt-row"><strong>Email</strong><span><?= htmlspecialchars($receipt["email"]) ?></span></div>
            <div class="receipt-row"><strong>Phone</strong><span><?= htmlspecialchars($receipt["phone_number"] ?: "N/A") ?></span></div>
            <div class="receipt-row"><strong>Doctor</strong><span>Dr. <?= htmlspecialchars($receipt["doctor_name"]) ?><?= !empty($receipt["doctor_specialization"]) ? " (" . htmlspecialchars($receipt["doctor_specialization"]) . ")" : "" ?></span></div>
            <div class="receipt-row"><strong>Appointment Date</strong><span><?= date("l, F j, Y", strtotime($receipt["appointment_date"])) ?></span></div>
            <div class="receipt-row"><strong>Appointment Time</strong><span><?= date("g:i A", strtotime($receipt["appointment_time"])) ?></span></div>
            <div class="receipt-row"><strong>Reason</strong><span><?= htmlspecialchars($receipt["reason"]) ?></span></div>
            <div class="receipt-row"><strong>Payment Method</strong><span><?= htmlspecialchars($receipt["payment_method"] ?: "N/A") ?></span></div>
            <div class="receipt-row"><strong>Payment Reference</strong><span><?= htmlspecialchars($receipt["payment_reference"]) ?></span></div>
            <div class="receipt-row"><strong>Confirmed On</strong><span><?= $receipt["payment_date"] ? date("d M Y, g:i A", strtotime($receipt["payment_date"])) : "N/A" ?></span></div>
            <div class="receipt-row"><strong>Amount Paid</strong><span><strong>KSh <?= number_format($receipt["payment_amount"], 2) ?></strong></span></div>

            <div style="margin-top:22px;display:flex;gap:10px" class="no-print">
                <button onclick="window.print()" class="ab-btn ab-btn-primary"><i class="fas fa-print"></i> Print / Save Receipt</button>
                <a href="dashboard.php" class="ab-btn ab-btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
