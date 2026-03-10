<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

if (!isset($_GET['appointment_id'])) {
    header('Location: dashboard.php');
    exit;
}
$appointment_id = intval($_GET['appointment_id']);
$user_id = $_SESSION['user_id'];

// verify record exists and doctor owns appointment
$stmt = $conn->prepare("SELECT a.doctor_id FROM appointments a WHERE a.appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();
$appt = $res->fetch_assoc();

if (!$appt || $appt['doctor_id'] != $user_id) {
    header('Location: dashboard.php');
    exit;
}

// delete record
$stmt = $conn->prepare("DELETE FROM medical_records WHERE appointment_id=?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();

// optionally revert appointment status
$conn->query("UPDATE appointments SET status='scheduled' WHERE appointment_id=$appointment_id");

header('Location: dashboard.php?msg=' . urlencode('Medical record deleted.'));
exit;
