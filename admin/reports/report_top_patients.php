<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";

// fetch top patients by appointment count
$query = "
    SELECT u.full_name AS patient, COUNT(*) AS cnt
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    GROUP BY a.patient_id
    ORDER BY cnt DESC
    LIMIT 10
";
$res = $conn->query($query);
$labels = [];
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $labels[] = $row['patient'];
        $data[] = (int)$row['cnt'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Top Active Patients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 60px; }
        .chart-wrapper { max-width: 700px; margin: auto; }
        canvas { width: 100% !important; height: auto !important; }
    </style>
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">
    <h3 class="text-primary mb-4">Top Active Patients</h3>

    <div class="chart-wrapper bg-white p-4 shadow rounded" style="height:240px;">
        <canvas id="topPatientsChart"></canvas>
    </div>

    <?php if ($res && $res->num_rows > 0): ?>
        <table class="table table-bordered mt-4 bg-white shadow">
            <thead class="table-secondary">
                <tr><th>Patient</th><th>Appointments</th></tr>
            </thead>
            <tbody>
                <?php
global $conn;
                $r2 = $conn->query($query);
                while ($row = $r2->fetch_assoc()): ?>
                    <tr><td><?= htmlspecialchars($row['patient']) ?></td><td><?= $row['cnt'] ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="mt-4 text-muted">No data available yet.</p>
    <?php endif; ?>
</div>

<script>
const ctx2 = document.getElementById('topPatientsChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels); ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode($data); ?>,
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Top 10 Patients by Appointments' }
        },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include "../footer.php"; ?>
</body>
</html>