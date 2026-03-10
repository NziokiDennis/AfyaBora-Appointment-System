<?php
require_once "config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $phone_number = trim($_POST["phone_number"]);
    $role = $_POST["role"]; // Only 'doctor' or 'patient'

    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required!";
    } elseif (!in_array($role, ["doctor", "patient"])) {
        $error = "Invalid role selection!";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone_number, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $email, $password_hash, $phone_number, $role);

        if ($stmt->execute()) {
            header("Location: login.php?registered=true");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bilpham Outpatient System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"> <!-- Bootstrap -->
    <style>
        body { background-color: #f4f4f4; }
        .register-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .btn-primary { width: 100%; }
        .error { color: red; font-size: 14px; }
    </style>
</head>
<body>

    <?php include "partials/navbar.php"; ?>

    <div class="register-container">
        <h2>Register</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" action="register.php">
            <div class="mb-3">
                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="mb-3">
                <input type="text" name="phone_number" class="form-control" placeholder="Phone Number">
            </div>
            <div class="mb-3">
                <select name="role" class="form-control" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="doctor">Doctor</option>
                    <option value="patient">Patient</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p class="mt-3">Already have an account? <a href="login.php">Login</a></p>
    </div>

    <?php include "partials/footer.php"; ?>

</body>
</html>
