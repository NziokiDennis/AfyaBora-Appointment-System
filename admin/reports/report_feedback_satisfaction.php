<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";

// Prepare data containers
$doctorNames = $avgRatings = $feedbackCounts = $ratingDistribution = [];

// Fetch doctor feedback data
$query = "
    SELECT u.full_name AS doctor_name, 
           ROUND(AVG(f.rating), 2) AS avg_rating, 
           COUNT(f.feedback_id) AS total_feedback
    FROM feedback f
    JOIN users u ON f.doctor_id = u.user_id
    WHERE u.role = 'doctor'
    GROUP BY f.doctor_id
    ORDER BY avg_rating DESC
";

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $doctorNames[] = $row["doctor_name"];
    $avgRatings[] = $row["avg_rating"];
    $feedbackCounts[] = $row["total_feedback"];
}

// Fetch global rating distribution
$ratingResult = $conn->query("
    SELECT rating, COUNT(*) AS count 
    FROM feedback 
    GROUP BY rating 
    ORDER BY rating
");

$ratingLabels = [];
$ratingCounts = [];
while ($row = $ratingResult->fetch_assoc()) {
    $ratingLabels[] = $row['rating'] . " ⭐";
    $ratingCounts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback & Satisfaction Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-wrapper { max-width:700px; margin:auto; }
        canvas { width:100% !important; height:auto !important; }
    </style>
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">
    <h3 class="text-primary mb-4">🗣️ Feedback & Satisfaction Report</h3>

    <!-- Doctor Rating Summary -->
    <div class="chart-wrapper bg-white p-4 shadow rounded mb-5" style="height:200px;">
        <h5 class="mb-3">Doctor Ratings Overview</h5>
        <canvas id="ratingsChart"></canvas>
    </div>

    <!-- Rating Distribution -->
    <div class="chart-wrapper bg-white p-4 shadow rounded mb-5" style="height:200px;">
        <h5 class="mb-3">Rating Distribution (All Feedback)</h5>
        <canvas id="distributionChart"></canvas>
    </div>
</div>

<script>
const ratingsChart = new Chart(document.getElementById('ratingsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($doctorNames); ?>,
        datasets: [
            {
                label: 'Average Rating',
                data: <?= json_encode($avgRatings); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.6)'
            },
            {
                label: 'Feedback Count',
                data: <?= json_encode($feedbackCounts); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.6)'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Doctor Feedback Summary'
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

const distributionChart = new Chart(document.getElementById('distributionChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($ratingLabels); ?>,
        datasets: [{
            label: 'Rating Distribution',
            data: <?= json_encode($ratingCounts); ?>,
            backgroundColor: [
                '#dc3545', '#fd7e14', '#ffc107', '#0d6efd', '#198754'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Overall Rating Breakdown'
            }
        }
    }
});
</script>

<?php include "../footer.php"; ?>
</body>
</html>
