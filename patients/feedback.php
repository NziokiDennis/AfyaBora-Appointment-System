<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
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
        JOIN appointments a ON a.doctor_id = u.user_id
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
    <title>Doctor Feedback</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .feedback-card {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-primary { width: 100%; }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container">
    <div class="feedback-card">
        <h2 class="text-center">Doctor Feedback</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($doctors_result && $doctors_result->num_rows > 0): ?>
            <form method="POST" action="feedback.php">
                <div class="mb-3">
                    <label>Select Doctor</label>
                    <select name="doctor_id" class="form-control" required>
                        <option value="" disabled selected>Choose a doctor</option>
                        <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                            <option value="<?php echo $doctor["user_id"]; ?>"><?php echo $doctor["full_name"]; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Rating (1-5)</label>
                    <select name="rating" class="form-control" required>
                        <option value="1">⭐ 1 - Poor</option>
                        <option value="2">⭐⭐ 2 - Fair</option>
                        <option value="3">⭐⭐⭐ 3 - Good</option>
                        <option value="4">⭐⭐⭐⭐ 4 - Very Good</option>
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Comments</label>
                    <textarea name="comments" class="form-control" rows="4" placeholder="Write your feedback..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info mt-3">No completed appointments found. You can only rate doctors you've seen.</div>
        <?php endif; ?>
    </div>
</div>

<?php include "../partials/footer.php"; ?>

</body>
</html>
