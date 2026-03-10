<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['appointment_id'])) {
    header('Location: dashboard.php');
    exit;
}

$appointment_id = intval($_POST['appointment_id']);
$user_id = $_SESSION['user_id'];

// verify ownership and current status
$stmt = $conn->prepare("SELECT a.patient_id, a.status, a.appointment_date, u.user_id as doctor_user
                       FROM appointments a
                       JOIN patients p ON a.patient_id = p.patient_id
                       JOIN users u ON a.doctor_id = u.user_id
                       WHERE a.appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();
$appt = $res->fetch_assoc();

if (!$appt) {
    $error = "Appointment not found.";
} elseif ($appt['status'] !== 'scheduled') {
    $error = "Only scheduled appointments can be cancelled.";
} else {
    // optionally prevent cancellation same-day or past
    $today = date('Y-m-d');
    if ($appt['appointment_date'] < $today) {
        $error = "Cannot cancel an appointment that has already occurred.";
    } else {
        // update status
        $upd = $conn->prepare("UPDATE appointments SET status='canceled', updated_at=NOW(), updated_by=? WHERE appointment_id=?");
        $upd->bind_param("ii", $user_id, $appointment_id);
        if ($upd->execute()) {
            // log
            $log = $conn->prepare("INSERT INTO appointment_logs (appointment_id, changed_by, old_status, new_status, notes) VALUES (?, ?, 'scheduled', 'canceled', ?) ");
            $note = 'Cancelled by patient';
            $log->bind_param("iis", $appointment_id, $user_id, $note);
            $log->execute();
            $msg = "Appointment cancelled successfully.";
        } else {
            $error = "Failed to cancel appointment.";
        }
    }
}

$redirect = 'dashboard.php';
if (isset($msg)) {
    $redirect .= '?msg=' . urlencode($msg);
} elseif (isset($error)) {
    $redirect .= '?error=' . urlencode($error);
}
header('Location: ' . $redirect);
exit;
