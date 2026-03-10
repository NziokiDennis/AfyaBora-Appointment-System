<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";

// Fetch appointment stats grouped by doctor and status
$query = "
SELECT 
    u.user_id,
    u.full_name,
    COUNT(a.appointment_id) AS total_appointments,
    SUM(a.status = 'scheduled') AS scheduled,
    SUM(a.status = 'completed') AS completed,
    SUM(a.status = 'canceled') AS canceled
FROM users u
LEFT JOIN appointments a ON u.user_id = a.doctor_id
WHERE u.role = 'doctor'
GROUP BY u.user_id, u.full_name
ORDER BY total_appointments DESC
LIMIT 5
";

$res = $conn->query($query);
if (!$res) {
    die("SQL Error: " . $conn->error);
}

$labels = [];
$totalAppointmentsData = [];
$scheduledData = [];
$completedData = [];
$canceledData = [];

while ($row = $res->fetch_assoc()) {
    $labels[] = $row['full_name'];
    $totalAppointmentsData[] = (int)$row['total_appointments'];
    $scheduledData[] = (int)$row['scheduled'];
    $completedData[] = (int)$row['completed'];
    $canceledData[] = (int)$row['canceled'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Workload Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 60px; }
        .chart-wrapper { max-width: 700px; margin:auto; }
        canvas { width:100% !important; height:auto !important; }
    </style>
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container">
    <h3 class="text-primary mb-4">👩‍⚕️ Doctor Workload Report (Top 5)</h3>

    <div class="chart-wrapper bg-white p-4 shadow rounded" style="height:240px;">
        <canvas id="workloadChart"></canvas>
    </div>
</div>

<script>
const ctx = document.getElementById('workloadChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels); ?>,
        datasets: [
            {
                label: 'Total Appointments',
                data: <?= json_encode($totalAppointmentsData); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.7)'
            },
            {
                label: 'Scheduled',
                data: <?= json_encode($scheduledData); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.7)'
            },
            {
                label: 'Completed',
                data: <?= json_encode($completedData); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.7)'
            },
            {
                label: 'Canceled',
                data: <?= json_encode($canceledData); ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.7)'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        },
        plugins: {
            title: {
                display: true,
                text: 'Doctor Appointment Workload Breakdown',
                font: { size: 18 }
            }
        }
    }
});
</script>

<?php include "../footer.php"; ?>

</body>
</html>
