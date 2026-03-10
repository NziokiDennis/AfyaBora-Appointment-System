<?php
require_once "config/db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Fetch user (Only doctors & patients)
    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ? AND role IN ('doctor', 'patient')");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $full_name, $password_hash, $role);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $password_hash)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["full_name"] = $full_name;
            $_SESSION["role"] = $role;

            // Redirect based on role
            if ($role === "doctor") {
                header("Location: doctors/dashboard.php");
            } else {
                header("Location: patients/dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "User not found.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bilpham Outpatient System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"> <!-- Bootstrap -->
    <style>
        body { background-color: #f4f4f4; }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
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

    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" action="login.php">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p class="mt-3">Don't have an account? <a href="register.php">Register</a></p>
    </div>

    <?php include "partials/footer.php"; ?>

</body>
</html>
