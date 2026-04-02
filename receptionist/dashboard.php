<?php
require_once "../config/auth.php";
checkRole("receptionist");
require_once "../config/db.php";

$pendingPayments = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments WHERE payment_status='pending' AND status='scheduled'")->fetch_assoc()["c"];
$confirmedToday = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments WHERE payment_status='paid' AND DATE(payment_date)=CURDATE()")->fetch_assoc()["c"];
$upcomingAppointments = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled' AND appointment_date >= CURDATE()")->fetch_assoc()["c"];

$queue = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.payment_amount, a.payment_reference,
           puser.full_name AS patient_name, d.full_name AS doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users puser ON p.user_id = puser.user_id
    JOIN users d ON a.doctor_id = d.user_id
    WHERE a.payment_status = 'pending'
      AND a.payment_reference IS NOT NULL
      AND a.payment_reference != ''
    ORDER BY a.created_at ASC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0a1628;
            --blue: #1a6fe8;
            --blue2: #1259c4;
            --sky: #e8f2ff;
            --white: #ffffff;
            --muted: #6b7a99;
            --border: rgba(26,111,232,0.14);
        }
        body {
            background:
                radial-gradient(circle at top right, rgba(26,111,232,.12), transparent 35%),
                linear-gradient(180deg, #f4f8ff 0%, #edf4ff 100%);
            color: var(--navy);
        }
        .hero-card,
        .queue-card,
        .metric-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(10,22,40,.08);
        }
        .hero-card {
            padding: 28px;
            margin-bottom: 24px;
        }
        .metric-card {
            padding: 22px;
            height: 100%;
        }
        .metric-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--sky);
            color: var(--blue);
            font-size: 1.2rem;
            margin-bottom: 14px;
        }
        .metric-card .display-6 {
            font-weight: 700;
            color: var(--blue2);
        }
        .queue-card {
            overflow: hidden;
        }
        .queue-card .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            padding: 18px 22px;
        }
        .table thead th {
            background: #f7fbff;
            color: var(--muted);
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-bottom: 1px solid var(--border);
        }
    </style>
</head>
<body>
    <?php include "navbar.php"; ?>
    <div class="container mt-5">
        <div class="hero-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="mb-2">Receptionist Dashboard</h2>
                    <p class="text-muted mb-0">Track submitted payments, confirm bookings after payment, and keep the front desk flow moving.</p>
                </div>
                <a href="payments.php" class="btn btn-primary btn-lg" style="background:linear-gradient(135deg,#1a6fe8,#1259c4);border:none;border-radius:12px;">
                    <i class="fas fa-credit-card me-2"></i>Open Payment Desk
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="metric-card"><div class="metric-icon"><i class="fas fa-hourglass-half"></i></div><h6 class="text-muted">Pending Payment Confirmations</h6><div class="display-6"><?= $pendingPayments ?></div></div></div>
            <div class="col-md-4"><div class="metric-card"><div class="metric-icon"><i class="fas fa-circle-check"></i></div><h6 class="text-muted">Payments Confirmed Today</h6><div class="display-6"><?= $confirmedToday ?></div></div></div>
            <div class="col-md-4"><div class="metric-card"><div class="metric-icon"><i class="fas fa-calendar-check"></i></div><h6 class="text-muted">Scheduled Appointments</h6><div class="display-6"><?= $upcomingAppointments ?></div></div></div>
        </div>

        <div class="queue-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Pending Payment Queue</strong>
                <a href="payments.php" class="btn btn-outline-primary btn-sm">Open Payment Desk</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Reference</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($queue && $queue->num_rows > 0): ?>
                                <?php while ($row = $queue->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row["patient_name"]) ?></td>
                                        <td>Dr. <?= htmlspecialchars($row["doctor_name"]) ?></td>
                                        <td><?= htmlspecialchars($row["appointment_date"]) ?></td>
                                        <td><?= date("g:i A", strtotime($row["appointment_time"])) ?></td>
                                        <td><?= htmlspecialchars($row["payment_reference"] ?: "Awaiting ref") ?></td>
                                        <td>KSh <?= number_format($row["payment_amount"], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4">No pending payment confirmations right now.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include "../partials/footer.php"; ?>
</body>
</html>
