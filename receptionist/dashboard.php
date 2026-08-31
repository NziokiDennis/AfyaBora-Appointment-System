<?php
require_once "../config/auth.php";
checkRole("receptionist");
require_once "../config/db.php";

$current_page = "dashboard";

$pendingPayments = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments WHERE payment_status='pending' AND status='scheduled'")->fetch_assoc()["c"];
$confirmedToday = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments WHERE payment_status='paid' AND DATE(payment_date)=CURDATE()")->fetch_assoc()["c"];
$upcomingAppointments = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled' AND appointment_date >= CURDATE()")->fetch_assoc()["c"];

$queue = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.payment_amount, a.payment_reference,
           puser.full_name AS patient_name, d.full_name AS doctor_name
    FROM appointments a
    JOIN users puser ON a.patient_id = puser.user_id
    JOIN users d ON a.doctor_id = d.user_id AND d.role = 'doctor'
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
    <title>Receptionist Dashboard — AfyaBora</title>
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
            <div class="ab-greeting">Receptionist Dashboard</div>
            <div class="ab-subgreeting">Track payments and keep the front desk moving.</div>
        </div>
        <div class="ab-topbar-right">
            <a href="payments.php" class="ab-btn ab-btn-primary"><i class="fas fa-credit-card"></i> Open Payment Desk</a>
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'R', 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">Dashboard</div>
        <div class="ab-page-sub">Submitted payments, confirmations, and today's booking flow.</div>

        <div class="ab-stat-row">
            <div class="ab-stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-hourglass-half"></i></div>
                <div><div class="ab-stat-num"><?= $pendingPayments ?></div><div class="ab-stat-label">Pending Payment Confirmations</div></div>
            </div>
            <div class="ab-stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-circle-check"></i></div>
                <div><div class="ab-stat-num"><?= $confirmedToday ?></div><div class="ab-stat-label">Payments Confirmed Today</div></div>
            </div>
            <div class="ab-stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-calendar-check"></i></div>
                <div><div class="ab-stat-num"><?= $upcomingAppointments ?></div><div class="ab-stat-label">Scheduled Appointments</div></div>
            </div>
        </div>

        <div class="ab-card">
            <div class="ab-card-title" style="display:flex;justify-content:space-between;align-items:center">
                <span><i class="fas fa-list-check"></i> Pending Payment Queue</span>
                <a href="payments.php" class="ab-btn ab-btn-secondary ab-btn-sm">Open Payment Desk</a>
            </div>
            <?php if ($queue && $queue->num_rows > 0): ?>
                <?php while ($row = $queue->fetch_assoc()): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-receipt"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($row['patient_name']) ?></span>
                                <span class="ab-pill ab-pill-neutral">Dr. <?= htmlspecialchars($row['doctor_name']) ?></span>
                            </div>
                            <div class="alr-meta">
                                <?= date('D, M j, Y', strtotime($row['appointment_date'])) ?> · <?= date('g:i A', strtotime($row['appointment_time'])) ?>
                                · Ref: <?= htmlspecialchars($row['payment_reference'] ?: 'Awaiting ref') ?>
                            </div>
                        </div>
                        <div class="alr-trailing">
                            <span class="ab-pill ab-pill-blue">KSh <?= number_format($row['payment_amount'], 2) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin:10px 0 0">No pending payment confirmations right now.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
