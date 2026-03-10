e<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];

// Fetch patient details
$query = "SELECT p.date_of_birth, p.gender, p.address, u.phone_number 
          FROM patients p 
          JOIN users u ON p.user_id = u.user_id 
          WHERE p.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Step 1: Get patient_id
$patient_id_stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$patient_id_stmt->bind_param("i", $user_id);
$patient_id_stmt->execute();
$pid_result = $patient_id_stmt->get_result();
$pid_data = $pid_result->fetch_assoc();

$appointments = false;

if ($pid_data) {
    $patient_id = $pid_data["patient_id"];

    // Step 2: Fetch appointments using patient_id
    $appointments_query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
                                  a.payment_status, a.payment_amount, u.full_name AS doctor_name 
                           FROM appointments a 
                           JOIN users u ON a.doctor_id = u.user_id 
                           WHERE a.patient_id = ? 
                           AND a.status = 'scheduled' 
                           ORDER BY a.appointment_date ASC";
    $stmt = $conn->prepare($appointments_query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointments = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 80px; }
        .dashboard-card { padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .profile-card, .appointments-card, .feedback-card { background: white; }
        .dashboard-card h5 { color: #007bff; }
        .logout-btn { position: absolute; right: 20px; top: 20px; }
    </style>
</head>
<body>

    <?php include "navbar.php"; ?>

    <div class="container">
        <h2>Welcome, <?php echo $_SESSION["full_name"]; ?>!</h2>
        
        <div class="row mt-4">
            <!-- Profile Card -->
            <div class="col-md-4">
                <div class="dashboard-card profile-card p-3">
                    <h5><i class="fas fa-user-circle"></i> My Profile</h5>
                    <p><strong>Date of Birth:</strong> <?php echo $patient['date_of_birth'] ?? 'Not provided'; ?></p>
                    <p><strong>Gender:</strong> <?php echo isset($patient['gender']) ? ucfirst($patient['gender']) : 'Not provided'; ?></p>
                    <p><strong>Address:</strong> <?php echo $patient['address'] ?? 'Not provided'; ?></p>
                    <p><strong>Phone:</strong> <?php echo $patient['phone_number'] ?? 'Not provided'; ?></p>
                    <a href="update_profile.php" class="btn btn-primary btn-sm">Update Profile</a>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="col-md-4">
                <div class="dashboard-card appointments-card p-3">
                    <h5><i class="fas fa-calendar-check"></i> Upcoming Appointments</h5>
                    <?php if ($appointments && $appointments->num_rows > 0): ?>
                        <?php while ($row = $appointments->fetch_assoc()): ?>
                            <div class="mb-3 p-2 border rounded">
                                <p class="mb-1">
                                    <strong><?php echo date("M d, Y", strtotime($row["appointment_date"])); ?></strong> at 
                                    <strong><?php echo date("g:i A", strtotime($row["appointment_time"])); ?></strong>
                                </p>
                                <p class="mb-1">Dr. <?php echo $row["doctor_name"]; ?></p>
                                <?php if ($row["payment_status"] == "paid"): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Payment Pending</span>
                                    <a href="payment.php?appointment_id=<?php echo $row["appointment_id"]; ?>" 
                                       class="btn btn-sm btn-danger mt-1">
                                        Pay Now (KSh <?php echo number_format($row["payment_amount"], 2); ?>)
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No upcoming appointments.</p>
                    <?php endif; ?>
                    <a href="book_appointment.php" class="btn btn-primary btn-sm">Book Appointment</a>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="col-md-4">
                <div class="dashboard-card feedback-card p-3">
                    <h5><i class="fas fa-comment-dots"></i> Doctor Feedback</h5>
                    <p>Submit feedback about your last consultation.</p>
                    <a href="feedback.php" class="btn btn-primary btn-sm">Give Feedback</a>
                </div>
            </div>
        </div>
    </div>

    <?php include "../partials/footer.php"; ?>

</body>
</html>
