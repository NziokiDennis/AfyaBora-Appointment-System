<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];

// Get patient ID from `patients` table
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient = $patient_result->fetch_assoc();
$patient_id = $patient["patient_id"];

// Fetch past medical records
$query = "SELECT m.record_id, m.diagnosis, m.prescription, m.notes, a.appointment_date, u.full_name AS doctor_name 
          FROM medical_records m
          JOIN appointments a ON m.appointment_id = a.appointment_id
          JOIN users u ON a.doctor_id = u.user_id
          WHERE a.patient_id = ? AND a.status = 'completed'
          ORDER BY a.appointment_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$records = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
            html, body {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .container {
            flex: 1; /* Pushes footer to the bottom */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        footer {
            background: #343a40;
            color: white;
            text-align: center;
            padding: 15px 0;
            width: 100%;
            position: relative;
            bottom: 0;
        }

        .table th, .table td { text-align: center; }
    </style>
</head>
<body>

    <?php include "navbar.php"; ?>

    <div class="container">
        <div class="history-card">
            <h2 class="text-center">Medical History</h2>
            <?php if ($records->num_rows > 0): ?>
                <table class="table table-bordered mt-3">
                    <thead class="table-primary">
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Prescription</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $record["appointment_date"]; ?></td>
                                <td><?php echo $record["doctor_name"]; ?></td>
                                <td><?php echo $record["diagnosis"]; ?></td>
                                <td><?php echo $record["prescription"]; ?></td>
                                <td><?php echo $record["notes"]; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center mt-3">No medical history available.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include "../partials/footer.php"; ?>

</body>
</html>
