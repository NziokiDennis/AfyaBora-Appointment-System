<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$doctor_id = $_SESSION["user_id"];
$doctor_name = $_SESSION["full_name"];

$stmt = $conn->prepare("
    SELECT f.rating, f.comments, f.created_at, u.full_name AS patient_name
    FROM feedback f
    JOIN patients p ON f.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    WHERE f.doctor_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Feedback</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f4f4; }
        .container { margin-top: 60px; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container">
    <h2 class="text-center">Feedback from Your Patients</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">From: <?php echo htmlspecialchars($row["patient_name"]); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">Rating: <?php echo str_repeat("⭐", $row["rating"]); ?> (<?php echo $row["rating"]; ?>/5)</h6>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($row["comments"])); ?></p>
                    <small class="text-muted">Submitted on <?php echo date("M d, Y", strtotime($row["created_at"])); ?></small>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="alert alert-info text-center">No feedback submitted yet.</p>
    <?php endif; ?>
</div>

<?php include "../partials/footer.php"; ?>

</body>
</html>
