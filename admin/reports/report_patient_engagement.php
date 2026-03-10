<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";

// Get unique patients per month
$engagementQuery = "
    SELECT MONTHNAME(appointment_date) AS month, COUNT(DISTINCT patient_id) AS unique_patients
    FROM appointments
    WHERE YEAR(appointment_date) = YEAR(CURDATE())
    GROUP BY MONTH(appointment_date), MONTHNAME(appointment_date)
    ORDER BY MONTH(appointment_date)
";

$res = $conn->query($engagementQuery);
$labels = $counts = [];
while ($row = $res->fetch_assoc()) {
    $labels[] = $row['month'];
    $counts[] = $row['unique_patients'];
}

// Top repeat patients (most appointments)
$repeatQuery = "
    SELECT u.full_name, COUNT(*) AS total_appointments
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    GROUP BY a.patient_id
    ORDER BY total_appointments DESC
    LIMIT 5
";
$repeatResult = $conn->query($repeatQuery);

// Average time between appointments (global)
$intervalQuery = "
    SELECT patient_id, appointment_date
    FROM appointments
    ORDER BY patient_id, appointment_date
";

$intervalResult = $conn->query($intervalQuery);
$lastDates = [];
$totalDays = 0;
$count = 0;

while ($row = $intervalResult->fetch_assoc()) {
    $pid = $row['patient_id'];
    $date = new DateTime($row['appointment_date']);

    if (isset($lastDates[$pid])) {
        $days = $lastDates[$pid]->diff($date)->days;
        $totalDays += $days;
        $count++;
    }

    $lastDates[$pid] = $date;
}
$avgGap = $count ? round($totalDays / $count, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Engagement Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-wrapper { max-width:700px; margin:auto; }
        canvas { width:100% !important; height:auto !important; }
    </style>
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">
    <h3 class="text-primary mb-4">👥 Patient Engagement Report</h3>

    <!-- Unique Patients Chart -->
    <div class="chart-wrapper bg-white p-4 shadow rounded mb-5" style="height:220px;">
        <h5 class="mb-3">Unique Patients with Appointments (Monthly)</h5>
        <canvas id="uniquePatientsChart"></canvas>
    </div>

    <!-- Top Repeat Patients -->
    <div class="bg-white p-4 shadow rounded mb-4">
        <h5 class="mb-3">Top 5 Most Frequent Patients</h5>
        <?php if ($repeatResult->num_rows > 0): ?>
            <table class="table table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th>Patient Name</th>
                        <th>Appointments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $repeatResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['total_appointments']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No repeat data available.</p>
        <?php endif; ?>
    </div>

    <!-- Average Gap -->
    <div class="alert alert-info text-center">
        <strong>📅 Average Time Between Appointments:</strong> <?php echo $avgGap; ?> days
    </div>
</div>

<script>
const ctx = document.getElementById('uniquePatientsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels); ?>,
        datasets: [{
            label: 'Unique Patients',
            data: <?= json_encode($counts); ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.7)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Monthly Unique Patient Activity'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                stepSize: 1
            }
        }
    }
});
</script>

<?php include "../footer.php"; ?>
</body>
</html>
