for <?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$success = "";
$error = "";

// Fetch available doctors
$doctors_query = "SELECT user_id, full_name FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($doctors_query);

// Handle appointment booking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = $_POST["doctor_id"];
    $appointment_date = $_POST["appointment_date"];
    $appointment_time = $_POST["appointment_time"];
    $reason = $_POST["reason"];
    $additional_notes = trim($_POST["additional_notes"]);

    // Validate selected date (must not be in the past)
    $current_date = date("Y-m-d");
    if ($appointment_date < $current_date) {
        $error = "You cannot book an appointment for a past date.";
    } else {
        // Get patient ID from `patients` table
        $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $patient_result = $stmt->get_result();
        $patient = $patient_result->fetch_assoc();

        // If patient record does not exist, create it
        if (!$patient) {
            $stmt = $conn->prepare("INSERT INTO patients (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $patient_id = $conn->insert_id; // Get newly inserted patient ID
        } else {
            $patient_id = $patient["patient_id"];
        }

        // Insert appointment
        $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, additional_notes) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iissss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $additional_notes);

        if ($stmt->execute()) {
            $appointment_id = $conn->insert_id;
            // Redirect to payment page
            header("Location: payment.php?appointment_id=" . $appointment_id);
            exit();
        } else {
            $error = "Error booking appointment.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .appointment-card {
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
        <div class="appointment-card">
            <h2 class="text-center">Book an Appointment</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="book_appointment.php">
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
                    <label>Appointment Date</label>
                    <input type="date" name="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-3">
                    <label>Appointment Time</label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Reason for Appointment</label>
                    <select name="reason" class="form-control" required>
                        <option value="Routine Check-up">Routine Check-up</option>
                        <option value="Follow-up">Follow-up</option>
                        <option value="New Symptoms">New Symptoms</option>
                        <option value="Chronic Condition">Chronic Condition</option>
                        <option value="Other">Other (Specify Below)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Additional Notes (Optional)</label>
                    <textarea name="additional_notes" class="form-control" rows="3" placeholder="Describe your symptoms or special requests"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Book Appointment</button>
            </form>
        </div>
    </div>

    <?php include "../partials/footer.php"; ?>

</body>
</html>
