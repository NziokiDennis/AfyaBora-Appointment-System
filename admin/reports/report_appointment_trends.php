<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";

// Fetch summary data: daily appointments for current month
$period = $_GET['period'] ?? 'month'; // day/week/month/year
$labels = $data = [];

switch ($period) {
    case 'week':
        $query = "
            SELECT WEEK(appointment_date) AS label, COUNT(*) AS count 
            FROM appointments 
            WHERE YEAR(appointment_date) = YEAR(CURDATE()) 
            GROUP BY label
            ORDER BY label
        ";
        break;
    case 'year':
        $query = "
            SELECT YEAR(appointment_date) AS label, COUNT(*) AS count 
            FROM appointments 
            GROUP BY label
            ORDER BY label
        ";
        break;
    case 'day':
        $query = "
            SELECT DATE(appointment_date) AS label, COUNT(*) AS count 
            FROM appointments 
            WHERE appointment_date >= CURDATE() - INTERVAL 30 DAY 
            GROUP BY label
            ORDER BY label
        ";
        break;
    default:
        // Default to month view
        $query = "
            SELECT MONTHNAME(appointment_date) AS label, MONTH(appointment_date) AS month_num, COUNT(*) AS count 
            FROM appointments 
            WHERE YEAR(appointment_date) = YEAR(CURDATE()) 
            GROUP BY month_num, label
            ORDER BY month_num
        ";
}

$res = $conn->query($query);
if (!$res) {
    die("SQL Error: " . $conn->error);
}

// Populate labels and data arrays for Chart.js
while ($row = $res->fetch_assoc()) {
    $labels[] = $row['label'];
    $data[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Trends Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f4f4f4;
        }
        .container {
            margin-top: 60px;
        }
        .btn-outline-primary.active {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">
    <h3 class="text-primary mb-4">📈 Appointment Trends Report</h3>

    <!-- Filter Buttons -->
    <div class="mb-4">
        <a href="?period=day" class="btn btn-outline-primary <?php if ($period=='day') echo 'active'; ?>">Daily</a>
        <a href="?period=week" class="btn btn-outline-primary <?php if ($period=='week') echo 'active'; ?>">Weekly</a>
        <a href="?period=month" class="btn btn-outline-primary <?php if ($period=='month') echo 'active'; ?>">Monthly</a>
        <a href="?period=year" class="btn btn-outline-primary <?php if ($period=='year') echo 'active'; ?>">Yearly</a>
    </div>

    <!-- Chart -->
    <div class="bg-white p-4 shadow rounded">
        <canvas id="appointmentChart" height="120"></canvas>
    </div>
</div>

<script>
const ctx = document.getElementById('appointmentChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels); ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode($data); ?>,
            fill: true,
            backgroundColor: 'rgba(0, 123, 255, 0.2)',
            borderColor: '#007bff',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'Appointments Over Time',
                font: { size: 18 }
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php include "../footer.php"; ?>
</body>
</html>
