<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";

// Fetch top 10 diagnoses
$query = "
    SELECT diagnosis, COUNT(*) AS count
    FROM medical_records
    WHERE diagnosis IS NOT NULL AND diagnosis <> ''
    GROUP BY diagnosis
    ORDER BY count DESC
    LIMIT 10
";
$result = $conn->query($query);

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row["diagnosis"];
    $data[] = $row["count"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Common Diagnoses Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">
    <h3 class="text-primary mb-4">🧬 Most Common Diagnoses</h3>
    <p class="text-muted">Top 10 diagnoses recorded across the system</p>

    <div class="bg-white p-4 shadow rounded">
        <?php if (count($labels) > 0): ?>
            <canvas id="diagnosisChart" height="150"></canvas>
        <?php else: ?>
            <p class="text-center">No diagnoses available to display.</p>
        <?php endif; ?>
    </div>
</div>

<script>
const ctx = document.getElementById('diagnosisChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels); ?>,
        datasets: [{
            label: 'Frequency',
            data: <?= json_encode($data); ?>,
            backgroundColor: '#6610f2'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Top Diagnoses in the Clinic'
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Occurrences'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Diagnosis'
                }
            }
        }
    }
});
</script>

<?php include "../footer.php"; ?>
</body>
</html>
