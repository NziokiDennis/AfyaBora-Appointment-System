<?php
require_once "../config/auth.php";
checkRole("doctor");
require_once "../config/db.php";

$doctor_id = $_SESSION["user_id"];
$doctor_name = $_SESSION["full_name"];
$current_page = "view_feedback";

$stmt = $conn->prepare("
    SELECT f.rating, f.comments, f.created_at, u.full_name AS patient_name
    FROM feedback f
    JOIN users u ON f.patient_id = u.user_id
    WHERE f.doctor_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->num_rows;

$avg_rating = 0;
if ($total > 0) {
    $sum_stmt = $conn->prepare("SELECT AVG(rating) AS avg_r FROM feedback WHERE doctor_id = ?");
    $sum_stmt->bind_param("i", $doctor_id);
    $sum_stmt->execute();
    $avg_rating = round((float)($sum_stmt->get_result()->fetch_assoc()["avg_r"] ?? 0), 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Feedback — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .fb-stars { color: #f59e0b; font-size: .8rem; letter-spacing: 1px; }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Patient Feedback</div>
            <div class="ab-subgreeting"><?= $total ?> review<?= $total === 1 ? '' : 's' ?><?= $total > 0 ? " · {$avg_rating}★ average" : '' ?></div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($doctor_name, 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($doctor_name) ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">Feedback from Your Patients</div>
        <div class="ab-page-sub">What patients are saying after their appointments.</div>

        <div class="ab-card">
            <?php if ($total > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-comment"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($row["patient_name"]) ?></span>
                                <span class="fb-stars"><?= str_repeat("★", (int)$row["rating"]) . str_repeat("☆", 5 - (int)$row["rating"]) ?></span>
                            </div>
                            <div class="alr-meta">Submitted <?= date("M j, Y", strtotime($row["created_at"])) ?></div>
                            <?php if (!empty($row['comments'])): ?><div class="alr-detail"><?= nl2br(htmlspecialchars($row["comments"])) ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin:6px 0 0">No feedback submitted yet.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
