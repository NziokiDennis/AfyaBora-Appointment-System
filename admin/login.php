<?php
require_once "../config/db.php";
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
        if ($password === $password_hash || password_verify($password, $password_hash)) {
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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — HealthAdmin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root {
  --bg:      #0f1117;
  --surface: #181c27;
  --surface2:#1e2335;
  --border:  rgba(255,255,255,.07);
  --teal:    #0ec4a4;
  --teal2:   #08a688;
  --teal-glow:rgba(14,196,164,.18);
  --rose:    #f05b70;
  --text:    #e8eaf2;
  --muted:   #6b7280;
  --font:    'Plus Jakarta Sans', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
  height: 100%;
  font-family: var(--font);
  background: var(--bg);
  color: var(--text);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Background grid */
body::before {
  content: '';
  position: fixed; inset: 0;
  background-image:
    linear-gradient(rgba(14,196,164,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(14,196,164,.04) 1px, transparent 1px);
  background-size: 44px 44px;
  pointer-events: none;
}

/* Glow orb */
body::after {
  content: '';
  position: fixed;
  top: -200px; left: 50%;
  transform: translateX(-50%);
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(14,196,164,.12) 0%, transparent 70%);
  pointer-events: none;
}

.login-wrap {
  width: 100%;
  max-width: 420px;
  padding: 20px;
  position: relative;
  z-index: 1;
  animation: fadeUp .5s ease both;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.login-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 40px 36px;
  box-shadow: 0 24px 80px rgba(0,0,0,.4);
}

.login-logo {
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 28px;
  gap: 12px;
}
.logo-icon {
  width: 44px; height: 44px;
  background: linear-gradient(135deg, var(--teal), var(--teal2));
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; color: #fff;
  box-shadow: 0 0 24px var(--teal-glow);
}
.logo-text { font-size: 1.3rem; font-weight: 700; }
.logo-text span { color: var(--teal); }

.login-title { font-size: 1.4rem; font-weight: 700; text-align: center; margin-bottom: 6px; }
.login-sub   { font-size: .83rem; color: var(--muted); text-align: center; margin-bottom: 28px; }

.error-box {
  background: rgba(240,91,112,.12);
  border: 1px solid rgba(240,91,112,.3);
  border-radius: 10px;
  padding: 11px 14px;
  font-size: .82rem;
  color: var(--rose);
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 20px;
}

.form-group { margin-bottom: 16px; }
.form-label {
  display: block;
  font-size: .78rem;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .06em;
  margin-bottom: 8px;
}
.input-wrap { position: relative; }
.input-icon {
  position: absolute;
  left: 14px; top: 50%; transform: translateY(-50%);
  color: var(--muted); font-size: .85rem;
  pointer-events: none;
}
.form-control {
  width: 100%;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 12px 14px 12px 40px;
  color: var(--text);
  font-family: var(--font);
  font-size: .88rem;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.form-control::placeholder { color: var(--muted); }
.form-control:focus {
  border-color: var(--teal);
  box-shadow: 0 0 0 3px var(--teal-glow);
}

.btn-login {
  width: 100%;
  background: linear-gradient(135deg, var(--teal), var(--teal2));
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 13px;
  font-family: var(--font);
  font-size: .9rem;
  font-weight: 700;
  cursor: pointer;
  margin-top: 8px;
  transition: opacity .2s, transform .15s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-login:hover { opacity: .9; transform: translateY(-1px); }
.btn-login:active { transform: translateY(0); }

.login-footer {
  text-align: center;
  margin-top: 24px;
  font-size: .75rem;
  color: var(--muted);
}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
      <div class="logo-text">Afya<span>Bora</span></div>
    </div>

    <h1 class="login-title">Welcome back</h1>
    <p class="login-sub">Sign in to your admin panel</p>

    <?php if ($error): ?>
    <div class="error-box">
      <i class="fas fa-circle-exclamation"></i>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-wrap">
          <i class="fas fa-user input-icon"></i>
          <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autofocus>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
        </div>
      </div>
      <button type="submit" class="btn-login">
        <i class="fas fa-arrow-right-to-bracket"></i> Sign In
      </button>
    </form>
  </div>

  <div class="login-footer">
    &copy; <?php echo date('Y'); ?> Bilpham Outpatient System
  </div>
</div>
</body>
</html>