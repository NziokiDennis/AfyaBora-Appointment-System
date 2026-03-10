<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$doctor_id = $_SESSION["user_id"];

$query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.payment_status, 
                 a.payment_amount, a.payment_date, u.full_name AS patient_name
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = ? AND a.status = 'scheduled'
          ORDER BY a.appointment_date, a.appointment_time";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scheduled Appointments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include "navbar.php"; ?>

    <div class="container mt-5">
        <h2>Scheduled Appointments</h2>
        <?php if ($result->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date("M d, Y", strtotime($row['appointment_date'])) ?></td>
                            <td><?= date("g:i A", strtotime($row['appointment_time'])) ?></td>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td>
                                <?php if ($row['payment_status'] == 'paid'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Paid
                                    </span>
                                    <br><small class="text-muted">KSh <?= number_format($row['payment_amount'], 2) ?></small>
                                    <?php if ($row['payment_date']): ?>
                                        <br><small class="text-muted"><?= date("M d, Y", strtotime($row['payment_date'])) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-exclamation-triangle"></i> Payment Pending
                                    </span>
                                    <br><small class="text-muted">KSh <?= number_format($row['payment_amount'], 2) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['payment_status'] == 'paid'): ?>
                                    <a href="add_medical_record.php?appointment_id=<?= $row['appointment_id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-file-medical"></i> Add Record
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled title="Payment required">
                                        <i class="fas fa-lock"></i> Locked
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No scheduled appointments found.</p>
        <?php endif; ?>
    </div>

    <?php include "../partials/footer.php"; ?>
</body>
</html>
