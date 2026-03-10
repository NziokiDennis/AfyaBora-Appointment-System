<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 60px; }
        .card {
            border: none;
            border-left: 5px solid #0d6efd;
            padding: 20px;
            transition: all 0.3s ease;
            height: 100%;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .card i {
            font-size: 2rem;
            color: #0d6efd;
            margin-bottom: 15px;
        }
        a { text-decoration: none; }
    </style>
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container">
    <h2 class="mb-4 text-center text-primary"><i class="fas fa-chart-bar me-2"></i>Reports Dashboard</h2>
    <div class="row g-4">

        <?php
        $reports = [
            ['Appointments Trends', 'report_appointment_trends.php', 'fa-calendar-line'],
            ['Doctor Workload', 'report_doctor_workload.php', 'fa-user-md'],
            ['Feedback & Satisfaction', 'report_feedback_satisfaction.php', 'fa-star'],
            ['Patient Engagement', 'report_patient_engagement.php', 'fa-users'],
            ['System Usage Overview', 'report_system_usage.php', 'fa-chart-pie'],
            ['Most Common Diagnoses', 'report_common_diagnoses.php', 'fa-notes-medical']
        ];

        foreach ($reports as $r): ?>
            <div class="col-md-6">
                <a href="<?php echo $r[1]; ?>">
                    <div class="card bg-white shadow-sm text-dark h-100">
                        <i class="fas <?php echo $r[2]; ?>"></i>
                        <h5 class="fw-bold"><?php echo $r[0]; ?></h5>
                        <p class="text-muted">View detailed analysis</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>

    </div>
</div>

<?php include "../footer.php"; ?>
</body>
</html>
