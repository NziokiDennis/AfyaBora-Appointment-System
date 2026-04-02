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
           d.full_name AS doctor_name, u.full_name AS patient_name, u.email, u.phone_number
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    JOIN users d ON a.doctor_id = d.user_id
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
    <title>Payment Receipt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f6f9; }
        .receipt-card {
            max-width: 760px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .receipt-header {
            background: #0d6efd;
            color: #fff;
            padding: 24px 28px;
        }
        .receipt-body { padding: 28px; }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .receipt-row:last-child { border-bottom: none; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .receipt-card { box-shadow: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="receipt-header">
            <h2 class="mb-1">AfyaBora Payment Receipt</h2>
            <div>Official receipt for confirmed appointment payment</div>
        </div>
        <div class="receipt-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1">Receipt No. <?= htmlspecialchars($receipt["payment_reference"]) ?></h5>
                    <div class="text-muted">Appointment #<?= (int)$receipt["appointment_id"] ?></div>
                </div>
                <span class="badge bg-success fs-6">Confirmed</span>
            </div>

            <div class="receipt-row"><strong>Patient</strong><span><?= htmlspecialchars($receipt["patient_name"]) ?></span></div>
            <div class="receipt-row"><strong>Email</strong><span><?= htmlspecialchars($receipt["email"]) ?></span></div>
            <div class="receipt-row"><strong>Phone</strong><span><?= htmlspecialchars($receipt["phone_number"] ?: "N/A") ?></span></div>
            <div class="receipt-row"><strong>Doctor</strong><span>Dr. <?= htmlspecialchars($receipt["doctor_name"]) ?></span></div>
            <div class="receipt-row"><strong>Appointment Date</strong><span><?= date("l, F j, Y", strtotime($receipt["appointment_date"])) ?></span></div>
            <div class="receipt-row"><strong>Appointment Time</strong><span><?= date("g:i A", strtotime($receipt["appointment_time"])) ?></span></div>
            <div class="receipt-row"><strong>Reason</strong><span><?= htmlspecialchars($receipt["reason"]) ?></span></div>
            <div class="receipt-row"><strong>Payment Method</strong><span><?= htmlspecialchars($receipt["payment_method"] ?: "N/A") ?></span></div>
            <div class="receipt-row"><strong>Payment Reference</strong><span><?= htmlspecialchars($receipt["payment_reference"]) ?></span></div>
            <div class="receipt-row"><strong>Confirmed On</strong><span><?= $receipt["payment_date"] ? date("d M Y, g:i A", strtotime($receipt["payment_date"])) : "N/A" ?></span></div>
            <div class="receipt-row"><strong>Amount Paid</strong><span><strong>KSh <?= number_format($receipt["payment_amount"], 2) ?></strong></span></div>

            <div class="mt-4 no-print">
                <button onclick="window.print()" class="btn btn-success">
                    Print / Save Receipt
                </button>
                <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
