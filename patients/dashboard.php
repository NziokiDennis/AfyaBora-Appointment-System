<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$current_page = "dashboard";

// Fetch patient details
$query = "SELECT p.date_of_birth, p.gender, p.address, u.phone_number, u.full_name
          FROM patients p
          JOIN users u ON p.user_id = u.user_id
          WHERE p.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Step 1: Get patient_id
$patient_id_stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$patient_id_stmt->bind_param("i", $user_id);
$patient_id_stmt->execute();
$pid_result = $patient_id_stmt->get_result();
$pid_data = $pid_result->fetch_assoc();

$appointments = false;
$next_appointment = null;
$recent_records = [];
$stat_upcoming = 0;
$stat_completed = 0;
$stat_total = 0;
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

function derivePaymentStage($appointment) {
    if (($appointment["payment_status"] ?? "") === "paid") {
        return "paid";
    }
    $hasSubmission = !empty($appointment["payment_reference"]);
    return $hasSubmission ? "pending" : "unpaid";
}

if ($pid_data) {
    $patient_id = $pid_data["patient_id"];

    $appointments_query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time,
                                  a.payment_status, a.payment_amount, a.payment_reference,
                                  u.full_name AS doctor_name, u.specialization AS doctor_specialization
                           FROM appointments a
                           JOIN users u ON a.doctor_id = u.user_id AND u.role = 'doctor'
                           WHERE a.patient_id = ?
                           AND a.status = 'scheduled'
                           ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    $stmt = $conn->prepare($appointments_query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointments = $stmt->get_result();

    if ($appointments && $appointments->num_rows > 0) {
        $all_upcoming = $appointments->fetch_all(MYSQLI_ASSOC);
        $next_appointment = $all_upcoming[0];
        $stat_upcoming = count($all_upcoming);
        // rewind for later loop by re-querying a result-like structure
        $appointments = $all_upcoming;
    } else {
        $appointments = [];
    }

    $cstmt = $conn->prepare("SELECT COUNT(*) AS c FROM appointments WHERE patient_id=? AND status='completed'");
    $cstmt->bind_param("i", $patient_id);
    $cstmt->execute();
    $stat_completed = (int)($cstmt->get_result()->fetch_assoc()['c'] ?? 0);

    $tstmt = $conn->prepare("SELECT COUNT(*) AS c FROM appointments WHERE patient_id=?");
    $tstmt->bind_param("i", $patient_id);
    $tstmt->execute();
    $stat_total = (int)($tstmt->get_result()->fetch_assoc()['c'] ?? 0);

    $rec_stmt = $conn->prepare("
        SELECT m.diagnosis, m.prescription, a.appointment_date, u.full_name AS doctor_name
        FROM medical_records m
        JOIN appointments a ON m.appointment_id = a.appointment_id
        JOIN users u ON a.doctor_id = u.user_id AND u.role = 'doctor'
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC
        LIMIT 3
    ");
    $rec_stmt->bind_param("i", $patient_id);
    $rec_stmt->execute();
    $recent_records = $rec_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$initials = "";
foreach (explode(" ", trim($patient['full_name'] ?? $_SESSION["full_name"] ?? "")) as $part) {
    if ($part !== "") $initials .= strtoupper($part[0]);
    if (strlen($initials) >= 2) break;
}
$first_name = explode(" ", trim($_SESSION["full_name"] ?? ""))[0] ?? "there";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .ab-grid { display: grid; grid-template-columns: 1fr 380px; gap: 22px; align-items: start; }
        @media (max-width: 1100px) { .ab-grid { grid-template-columns: 1fr; } }

        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 22px; }
        @media (max-width: 700px) { .stat-row { grid-template-columns: 1fr; } }
        .stat-tile { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px 20px; display: flex; align-items: center; gap: 14px; }
        .stat-tile .stat-num { font-size: 1.5rem; font-weight: 800; color: var(--navy); line-height: 1; }
        .stat-tile .stat-lbl { color: var(--muted); font-size: .76rem; margin-top: 4px; }

        .appt-row { border-bottom: 1px solid var(--border); padding: 16px 0; display: flex; align-items: center; gap: 14px; }
        .appt-row:last-child { border-bottom: none; padding-bottom: 4px; }
        .appt-row .appt-body { flex: 1; min-width: 0; }
        .appt-row .appt-name-line { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .appt-row .appt-name { font-weight: 600; color: var(--navy); font-size: .92rem; }
        .appt-row .appt-spec-tag { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: var(--radius-pill); border: 1px solid var(--border); color: var(--muted); font-size: .68rem; font-weight: 600; }
        .appt-row .appt-meta { color: var(--muted); font-size: .78rem; margin-top: 3px; }
        .appt-row .appt-trailing { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
        .appt-row .appt-trailing-buttons { display: flex; gap: 6px; }
        .appt-row .appt-ref { color: var(--muted); font-size: .7rem; }

        .profile-line { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--border); font-size: .84rem; }
        .profile-line:last-of-type { border-bottom: none; }
        .profile-line .pl-label { color: var(--muted); }
        .profile-line .pl-value { color: var(--navy); font-weight: 600; text-align: right; }

        .empty-state { text-align: center; padding: 30px 10px; color: var(--muted); font-size: .85rem; }
        .empty-state i { font-size: 1.8rem; color: var(--border); margin-bottom: 10px; display: block; }

        /* Next-appointment highlight block, right rail */
        .next-appt-datebox {
            background: var(--sky); border-radius: var(--radius-md);
            padding: 10px 14px; text-align: center; min-width: 64px;
        }
        .next-appt-datebox .nd-day { font-size: 1.4rem; font-weight: 800; color: var(--navy); line-height: 1; }
        .next-appt-datebox .nd-month { font-size: .68rem; font-weight: 700; color: var(--blue); text-transform: uppercase; margin-top: 2px; }

        .record-row { border-bottom: 1px solid var(--border); padding: 12px 0; display: flex; align-items: center; gap: 12px; }
        .record-row:last-child { border-bottom: none; padding-bottom: 0; }
        .record-row .rr-diagnosis { font-weight: 600; color: var(--navy); font-size: .85rem; }
        .record-row .rr-meta { color: var(--muted); font-size: .74rem; margin-top: 2px; }
        .record-row .rr-view { color: var(--border); font-size: .8rem; flex-shrink: 0; transition: color .15s; }
        .record-row .rr-view:hover { color: var(--blue); }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Welcome back, <?= htmlspecialchars($first_name) ?></div>
            <div class="ab-subgreeting">Here's what's happening with your care.</div>
        </div>
        <div class="ab-topbar-right">
            <a href="book_appointment.php" class="ab-btn ab-btn-primary"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars($initials ?: "P") ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION["full_name"] ?? "") ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">Dashboard</div>
        <div class="ab-page-sub">An overview of your appointments, records, and profile.</div>

        <?php if ($msg): ?><div class="ab-alert ab-alert-success"><i class="fas fa-circle-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($error): ?><div class="ab-alert ab-alert-danger"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

        <div class="stat-row">
            <div class="stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-calendar-check"></i></div>
                <div><div class="stat-num"><?= $stat_upcoming ?></div><div class="stat-lbl">Upcoming Appointments</div></div>
            </div>
            <div class="stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-circle-check"></i></div>
                <div><div class="stat-num"><?= $stat_completed ?></div><div class="stat-lbl">Completed Visits</div></div>
            </div>
            <div class="stat-tile">
                <div class="ab-icon-chip"><i class="fas fa-notes-medical"></i></div>
                <div><div class="stat-num"><?= count($recent_records) ?></div><div class="stat-lbl">Medical Records on File</div></div>
            </div>
        </div>

        <div class="ab-grid">
            <!-- Main column -->
            <div>
                <div class="ab-card" style="margin-bottom:22px">
                    <div class="ab-card-title"><i class="fas fa-calendar-check"></i> Upcoming Appointments</div>

                    <?php if (!empty($appointments)): ?>
                        <div style="margin-top:10px">
                        <?php foreach ($appointments as $row): ?>
                            <?php $payment_stage = derivePaymentStage($row); ?>
                            <div class="appt-row">
                                <div class="ab-icon-chip"><i class="fas fa-user-doctor"></i></div>
                                <div class="appt-body">
                                    <div class="appt-name-line">
                                        <span class="appt-name">Dr. <?= htmlspecialchars($row["doctor_name"]) ?></span>
                                        <?php if (!empty($row["doctor_specialization"])): ?>
                                            <span class="appt-spec-tag"><?= htmlspecialchars($row["doctor_specialization"]) ?></span>
                                        <?php endif; ?>
                                        <?php if ($payment_stage == "paid"): ?>
                                            <span class="ab-pill ab-pill-green"><i class="fas fa-check"></i> Paid</span>
                                        <?php elseif ($payment_stage == "pending"): ?>
                                            <span class="ab-pill ab-pill-amber"><i class="fas fa-hourglass-half"></i> Pending Confirmation</span>
                                        <?php else: ?>
                                            <span class="ab-pill ab-pill-rose"><i class="fas fa-triangle-exclamation"></i> Not Paid</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appt-meta">
                                        <?= date("D, M j, Y", strtotime($row["appointment_date"])) ?> &middot; <?= date("g:i A", strtotime($row["appointment_time"])) ?>
                                        <?php if (!empty($row["payment_reference"]) && $payment_stage === "pending"): ?>
                                            &middot; <span class="appt-ref">Ref: <?= htmlspecialchars($row["payment_reference"]) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="appt-trailing">
                                    <div class="appt-trailing-buttons">
                                        <?php if ($payment_stage == "paid"): ?>
                                            <a href="receipt.php?appointment_id=<?= $row["appointment_id"] ?>" class="ab-btn ab-btn-secondary ab-btn-sm"><i class="fas fa-receipt"></i> Receipt</a>
                                        <?php elseif ($payment_stage == "unpaid"): ?>
                                            <a href="payment.php?appointment_id=<?= $row["appointment_id"] ?>" class="ab-btn ab-btn-primary ab-btn-sm">Pay KSh <?= number_format($row["payment_amount"], 2) ?></a>
                                        <?php endif; ?>
                                        <form method="POST" action="cancel_appointment.php" style="display:inline">
                                            <input type="hidden" name="appointment_id" value="<?= $row["appointment_id"] ?>">
                                            <button type="submit" class="ab-btn ab-btn-danger ab-btn-sm" onclick="return confirm('Are you sure you want to cancel?');">Cancel</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-xmark"></i>
                            No upcoming appointments yet.<br>
                            <a href="book_appointment.php" class="ab-btn ab-btn-primary ab-btn-sm" style="margin-top:12px">Book your first appointment</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ab-card">
                    <div class="ab-card-title"><i class="fas fa-comment-dots"></i> Doctor Feedback</div>
                    <p style="color:var(--muted);font-size:.85rem;margin:8px 0 14px">Had a consultation recently? Let your doctor know how it went.</p>
                    <a href="feedback.php" class="ab-btn ab-btn-secondary"><i class="fas fa-paper-plane"></i> Give Feedback</a>
                </div>
            </div>

            <!-- Right rail -->
            <div>
                <?php if ($next_appointment): ?>
                <div class="ab-card" style="margin-bottom:18px">
                    <div class="ab-card-title" style="margin-bottom:14px"><i class="fas fa-star"></i> Your Next Appointment</div>
                    <div style="display:flex;gap:14px;align-items:center">
                        <div class="next-appt-datebox">
                            <div class="nd-day"><?= date("j", strtotime($next_appointment["appointment_date"])) ?></div>
                            <div class="nd-month"><?= date("M", strtotime($next_appointment["appointment_date"])) ?></div>
                        </div>
                        <div>
                            <div style="font-weight:700;color:var(--navy);font-size:.92rem">Dr. <?= htmlspecialchars($next_appointment["doctor_name"]) ?></div>
                            <?php if (!empty($next_appointment["doctor_specialization"])): ?>
                                <div style="color:var(--muted);font-size:.78rem"><?= htmlspecialchars($next_appointment["doctor_specialization"]) ?></div>
                            <?php endif; ?>
                            <div style="color:var(--muted);font-size:.78rem;margin-top:2px"><?= date("g:i A", strtotime($next_appointment["appointment_time"])) ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="ab-card" style="margin-bottom:18px">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                        <div class="ab-user-avatar" style="width:48px;height:48px;font-size:1rem"><?= htmlspecialchars($initials ?: "P") ?></div>
                        <div>
                            <div style="font-weight:700;color:var(--navy);font-size:.98rem"><?= htmlspecialchars($_SESSION["full_name"] ?? "") ?></div>
                            <div style="color:var(--muted);font-size:.78rem">Patient</div>
                        </div>
                    </div>
                    <div style="border-top:1px solid var(--border);margin-bottom:14px"></div>

                    <div class="profile-line">
                        <span class="pl-label">Date of Birth</span>
                        <span class="pl-value"><?= $patient['date_of_birth'] ? date("M j, Y", strtotime($patient['date_of_birth'])) : 'Not provided' ?></span>
                    </div>
                    <div class="profile-line">
                        <span class="pl-label">Gender</span>
                        <span class="pl-value"><?= isset($patient['gender']) ? ucfirst($patient['gender']) : 'Not provided' ?></span>
                    </div>
                    <div class="profile-line">
                        <span class="pl-label">Phone</span>
                        <span class="pl-value"><?= htmlspecialchars($patient['phone_number'] ?? 'Not provided') ?></span>
                    </div>
                    <div class="profile-line">
                        <span class="pl-label">Address</span>
                        <span class="pl-value"><?= htmlspecialchars($patient['address'] ?? 'Not provided') ?></span>
                    </div>

                    <a href="update_profile.php" class="ab-btn ab-btn-secondary" style="width:100%;justify-content:center;margin-top:16px">
                        <i class="fas fa-user-pen"></i> Update Profile
                    </a>
                </div>

                <div class="ab-card">
                    <div class="ab-card-title" style="margin-bottom:6px"><i class="fas fa-notes-medical"></i> Recent Medical Records</div>
                    <?php if ($recent_records): ?>
                        <div style="margin-top:8px">
                        <?php foreach ($recent_records as $rec): ?>
                            <div class="record-row">
                                <div class="ab-icon-chip" style="width:32px;height:32px;font-size:.8rem"><i class="fas fa-file-medical"></i></div>
                                <div style="flex:1;min-width:0">
                                    <div class="rr-diagnosis"><?= htmlspecialchars($rec["diagnosis"]) ?></div>
                                    <div class="rr-meta">Dr. <?= htmlspecialchars($rec["doctor_name"]) ?> &middot; <?= date("M j, Y", strtotime($rec["appointment_date"])) ?></div>
                                </div>
                                <a href="medical_history.php" class="rr-view" title="View in Medical History"><i class="fas fa-chevron-right"></i></a>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <a href="medical_history.php" class="ab-btn ab-btn-secondary" style="width:100%;justify-content:center;margin-top:14px">View Full History</a>
                    <?php else: ?>
                        <p style="color:var(--muted);font-size:.82rem;margin-top:8px">No medical records yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
