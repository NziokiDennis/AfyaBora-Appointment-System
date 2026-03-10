<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$doctor_name = $_SESSION["full_name"];

// Fetch upcoming appointments
$query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, u.full_name AS patient_name 
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = ? AND a.status = 'scheduled'
          ORDER BY a.appointment_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();

// Fetch completed medical records
$query = "SELECT a.appointment_date, u.full_name AS patient_name, m.diagnosis, m.prescription, m.notes 
          FROM medical_records m
          JOIN appointments a ON m.appointment_id = a.appointment_id
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = ? AND a.status = 'completed'
          ORDER BY a.appointment_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$medical_records = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .dashboard-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .table th, .table td { text-align: center; vertical-align: middle; }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container">
    <h2 class="text-center">Welcome, Dr. <?php echo $doctor_name; ?></h2>

    <!-- Upcoming Appointments -->
    <div class="dashboard-card mt-4">
        <h4 class="text-primary">Upcoming Appointments</h4>
        <?php if ($upcoming_appointments->num_rows > 0): ?>
            <table class="table table-bordered mt-3">
                <thead class="table-primary">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $appointment["appointment_date"]; ?></td>
                            <td><?php echo $appointment["appointment_time"]; ?></td>
                            <td><?php echo $appointment["patient_name"]; ?></td>
                            <td>
                                <a href="add_medical_record.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-primary">Add Record</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mt-3">No upcoming appointments.</p>
        <?php endif; ?>
    </div>

    <!-- Medical Records -->
    <div class="dashboard-card">
        <h4 class="text-secondary">Completed Medical Records</h4>
        <?php if ($medical_records->num_rows > 0): ?>
            <table class="table table-bordered mt-3">
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
                    <?php while ($record = $medical_records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $record["appointment_date"]; ?></td>
                            <td><?php echo $record["patient_name"]; ?></td>
                            <td><?php echo $record["diagnosis"]; ?></td>
                            <td><?php echo $record["prescription"]; ?></td>
                            <td><?php echo $record["notes"]; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mt-3">No completed medical records yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include "../partials/footer.php"; ?>

</body>
</html>
