<?php
require_once "../config/db.php";
// restrict admin session cookie to admin path and regenerate id on login
session_set_cookie_params(["path" => "/Appointment_system/admin", "domain" => $_SERVER['HTTP_HOST'], "httponly" => true, "secure" => false]);
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT admin_id, full_name, password_hash FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($admin_id, $full_name, $password_hash);
        $stmt->fetch();

        // Plaintext password check
        if ($password === $password_hash) {
            // regenerate session id to avoid fixation
            session_regenerate_id(true);
            $_SESSION["admin_id"] = $admin_id;
            $_SESSION["full_name"] = $full_name;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "Admin not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 8px rgba(0,0,0,0.1);
        }
        .login-header {
            background: #0d6efd;
            color: white;
            padding: 15px;
            border-radius: 5px 5px 0 0;
            margin: -30px -30px 20px -30px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header"><h3 class="mb-0">Admin Login</h3></div>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Login</button>
    </form>
</div>
</body>
</html>
