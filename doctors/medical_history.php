<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$doctor_id = $_SESSION["user_id"];

$query = "SELECT a.appointment_date, u.full_name AS patient_name,
                 m.diagnosis, m.prescription, m.notes
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          JOIN medical_records m ON a.appointment_id = m.appointment_id
          WHERE a.doctor_id = ? AND a.status = 'completed'
          ORDER BY a.appointment_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medical Records</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include "navbar.php"; ?>

    <div class="container mt-5">
        <h2>My Medical Records</h2>
        <?php if ($result->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead class="table-secondary">
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Diagnosis</th>
                        <th>Prescription</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['appointment_date'] ?></td>
                            <td><?= $row['patient_name'] ?></td>
                            <td><?= $row['diagnosis'] ?></td>
                            <td><?= $row['prescription'] ?></td>
                            <td><?= $row['notes'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No medical records found.</p>
        <?php endif; ?>
    </div>

    <?php include "../partials/footer.php"; ?>
</body>
</html>
