<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$current_page = "feedback";
$success = "";
$error = "";

// Get patient ID
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient = $patient_result->fetch_assoc();
$patient_id = $patient["patient_id"] ?? null;

// Fetch doctors with past completed appointments only
if ($patient_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT u.user_id, u.full_name
        FROM users u
        JOIN appointments a ON a.doctor_id = u.user_id AND u.role = 'doctor'
        JOIN medical_records m ON m.appointment_id = a.appointment_id
        WHERE a.patient_id = ?
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $doctors_result = $stmt->get_result();
} else {
    $doctors_result = false;
    $error = "Please update your profile or book and complete an appointment to give feedback.";
}

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $patient_id) {
    $doctor_id = $_POST["doctor_id"];
    $rating = $_POST["rating"];
    $comments = trim($_POST["comments"]);

    if (empty($doctor_id) || empty($rating) || empty($comments)) {
        $error = "All fields are required!";
    } else {
        // Validate doctor-patient appointment relationship
        $stmt = $conn->prepare("
            SELECT 1 FROM appointments a
            JOIN medical_records m ON m.appointment_id = a.appointment_id
            WHERE a.patient_id = ? AND a.doctor_id = ? LIMIT 1
        ");
        $stmt->bind_param("ii", $patient_id, $doctor_id);
        $stmt->execute();
        $relation = $stmt->get_result()->fetch_assoc();

        if (!$relation) {
            $error = "You can only give feedback to doctors you've completed appointments with.";
        } else {
            $stmt = $conn->prepare("INSERT INTO feedback (patient_id, doctor_id, rating, comments) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $patient_id, $doctor_id, $rating, $comments);
            if ($stmt->execute()) {
                $success = "Feedback submitted successfully!";
            } else {
                $error = "Error submitting feedback.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Feedback — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Doctor Feedback</div>
            <div class="ab-subgreeting">Rate and comment on a doctor you've completed a visit with.</div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION["full_name"] ?? "P", 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION["full_name"] ?? "") ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-center-viewport">
        <div class="ab-page-title">Give Feedback</div>
        <div class="ab-page-sub">Your rating and comments go directly to the doctor you saw.</div>

        <div class="ab-card">
            <?php if ($success): ?>
                <div class="ab-alert ab-alert-success"><i class="fas fa-circle-check"></i> <?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="ab-alert ab-alert-danger"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($doctors_result && $doctors_result->num_rows > 0): ?>
                <form method="POST" action="feedback.php">
                    <div class="ab-form-group">
                        <label class="ab-label">Select Doctor <span class="req">*</span></label>
                        <select name="doctor_id" class="ab-input" required>
                            <option value="" disabled selected>Choose a doctor</option>
                            <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                                <option value="<?php echo $doctor["user_id"]; ?>"><?php echo $doctor["full_name"]; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Rating (1-5) <span class="req">*</span></label>
                        <select name="rating" class="ab-input" required>
                            <option value="1">⭐ 1 - Poor</option>
                            <option value="2">⭐⭐ 2 - Fair</option>
                            <option value="3">⭐⭐⭐ 3 - Good</option>
                            <option value="4">⭐⭐⭐⭐ 4 - Very Good</option>
                            <option value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                        </select>
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Comments <span class="req">*</span></label>
                        <textarea name="comments" class="ab-input" rows="4" placeholder="Write your feedback..." required></textarea>
                    </div>
                    <button type="submit" class="ab-btn ab-btn-primary" style="width:100%;justify-content:center"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
                </form>
            <?php else: ?>
                <div class="ab-alert ab-alert-danger" style="margin-top:10px"><i class="fas fa-circle-info"></i> No completed appointments found. You can only rate doctors you've seen.</div>
            <?php endif; ?>
        </div>
        </div>
    </main>
</div>

</body>
</html>
