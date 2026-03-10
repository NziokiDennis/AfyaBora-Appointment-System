<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];

// Fetch existing profile data
$query = "SELECT u.full_name, u.email, u.phone_number, p.date_of_birth, p.gender, p.address 
          FROM users u 
          LEFT JOIN patients p ON u.user_id = p.user_id 
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $phone_number = trim($_POST["phone_number"]);
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $address = trim($_POST["address"]);

    // Update `users` table
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $full_name, $phone_number, $user_id);
    $stmt->execute();

    // Update or insert into `patients` table
    $stmt = $conn->prepare("INSERT INTO patients (user_id, date_of_birth, gender, address) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE date_of_birth = VALUES(date_of_birth), gender = VALUES(gender), address = VALUES(address)");
    $stmt->bind_param("isss", $user_id, $date_of_birth, $gender, $address);
    $stmt->execute();

    $success = "Profile updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Patient</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .profile-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-primary { width: 100%; }
    </style>
</head>
<body>

    <?php include "navbar.php"; ?>

    <div class="profile-container">
        <h2 class="text-center">Update Profile</h2>
        <?php if (isset($success)) echo "<p class='alert alert-success'>$success</p>"; ?>
        <form method="POST" action="update_profile.php">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo $patient['full_name']; ?>" required>
            </div>
            <div class="mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" value="<?php echo $patient['phone_number']; ?>">
            </div>
            <div class="mb-3">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?php echo $patient['date_of_birth']; ?>">
            </div>
            <div class="mb-3">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="male" <?php echo ($patient['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo ($patient['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo ($patient['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Address</label>
                <textarea name="address" class="form-control"><?php echo $patient['address']; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <?php include "../partials/footer.php"; ?>

</body>
</html>
