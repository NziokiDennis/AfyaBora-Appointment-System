<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";

// Fetch total counts
$totalPatients   = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'patient'")->fetch_assoc()["total"];
$totalDoctors    = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'doctor'")->fetch_assoc()["total"];
$totalFeedback   = $conn->query("SELECT COUNT(*) AS total FROM feedback")->fetch_assoc()["total"];
$totalRecords    = $conn->query("SELECT COUNT(*) AS total FROM medical_records")->fetch_assoc()["total"];
$totalAppointments = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()["total"];

// Fetch appointment status distribution
$statusQuery = "
    SELECT status, COUNT(*) AS count
    FROM appointments
    GROUP BY status
";

$statuses = ['scheduled' => 0, 'completed' => 0, 'canceled' => 0];
$result = $conn->query($statusQuery);
while ($row = $result->fetch_assoc()) {
    $statuses[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Usage Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">
    <h3 class="text-primary mb-4">📊 System Usage Overview</h3>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-primary"><i class="fas fa-users"></i> Total Patients</h5>
                    <p class="display-6"><?php echo $totalPatients; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-success"><i class="fas fa-user-md"></i> Total Doctors</h5>
                    <p class="display-6"><?php echo $totalDoctors; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-info"><i class="fas fa-calendar-check"></i> Total Appointments</h5>
                    <p class="display-6"><?php echo $totalAppointments; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-warning"><i class="fas fa-comments"></i> Feedback Submitted</h5>
                    <p class="display-6"><?php echo $totalFeedback; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-danger"><i class="fas fa-notes-medical"></i> Medical Records</h5>
                    <p class="display-6"><?php echo $totalRecords; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Status Pie Chart -->
    <div class="bg-white p-4 shadow rounded">
        <h5 class="mb-3">📅 Appointment Status Breakdown</h5>
        <canvas id="appointmentStatusChart" height="120"></canvas>
    </div>
</div>

<script>
const ctx = document.getElementById('appointmentStatusChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Scheduled', 'Completed', 'Canceled'],
        datasets: [{
            data: [
                <?= $statuses['scheduled'] ?>,
                <?= $statuses['completed'] ?>,
                <?= $statuses['canceled'] ?>
            ],
            backgroundColor: ['#0d6efd', '#198754', '#dc3545'],
            hoverOffset: 10
        }]
    },
    options: {
        plugins: {
            title: {
                display: true,
                text: 'Appointment Status Overview'
            },
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include "../footer.php"; ?>
</body>
</html>
