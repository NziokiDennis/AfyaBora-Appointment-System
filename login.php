<?php
require_once "config/db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ? AND role IN ('doctor', 'patient')");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $full_name, $password_hash, $role);
        $stmt->fetch();
        if (password_verify($password, $password_hash)) {
            $_SESSION["user_id"]   = $user_id;
            $_SESSION["full_name"] = $full_name;
            $_SESSION["role"]      = $role;
            header("Location: " . ($role === "doctor" ? "doctors/dashboard.php" : "patients/dashboard.php"));
            exit;
        } else { $error = "Invalid email or password."; }
    } else { $error = "No account found with that email."; }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — AfyaBora</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root {
  --navy:  #0a1628;
  --blue:  #1a6fe8;
  --blue2: #1259c4;
  --sky:   #e8f2ff;
  --white: #ffffff;
  --muted: #6b7a99;
  --border: rgba(26,111,232,0.14);
  --font:  'DM Sans', sans-serif;
  --font-d:'Sora', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: var(--font);
  background: #f0f6ff;
  color: var(--navy);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* NAV */
nav {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  box-shadow: 0 2px 16px rgba(10,22,40,0.06);
  position: sticky; top: 0; z-index: 100;
}
.nav-inner {
  max-width: 1100px; margin: 0 auto;
  padding: 0 28px; height: 64px;
  display: flex; align-items: center; gap: 16px;
}
.brand {
  font-family: var(--font-d); font-size: 1.05rem; font-weight: 700;
  color: var(--navy); text-decoration: none;
  display: flex; align-items: center; gap: 9px; margin-right: auto;
}
.brand-icon {
  width: 34px; height: 34px; border-radius: 9px;
  background: linear-gradient(135deg, var(--blue), var(--blue2));
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: .85rem;
  box-shadow: 0 4px 12px rgba(26,111,232,.28);
}
.brand span { color: var(--blue); }
.nav-link {
  font-size: .875rem; font-weight: 500; color: var(--muted);
  text-decoration: none; padding: 7px 13px; border-radius: 8px;
  transition: all .18s;
}
.nav-link:hover { color: var(--navy); background: var(--sky); }
.nav-link.active { color: var(--blue); background: var(--sky); font-weight: 600; }
.btn-nav {
  padding: 7px 18px; border-radius: 9px; font-size: .875rem; font-weight: 600;
  text-decoration: none; transition: all .18s; display: inline-flex; align-items: center; gap: 6px;
}
.btn-outline { color: var(--blue); border: 1.5px solid var(--blue); background: transparent; }
.btn-outline:hover { background: var(--sky); }
.btn-solid { background: var(--blue); color: #fff; border: 1.5px solid var(--blue); box-shadow: 0 4px 14px rgba(26,111,232,.22); }
.btn-solid:hover { background: var(--blue2); }

/* MAIN */
main {
  flex: 1; display: flex; align-items: center;
  justify-content: center; padding: 52px 24px;
}
.auth-wrap { width: 100%; max-width: 420px; }
.auth-icon-wrap {
  text-align: center; margin-bottom: 28px;
}
.auth-icon {
  width: 56px; height: 56px; border-radius: 16px;
  background: linear-gradient(135deg, var(--blue), var(--blue2));
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.4rem; color: #fff;
  box-shadow: 0 8px 24px rgba(26,111,232,.3);
  margin-bottom: 14px;
}
.auth-title { font-family: var(--font-d); font-size: 1.6rem; font-weight: 700; margin-bottom: 6px; }
.auth-sub { color: var(--muted); font-size: .9rem; }

.card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 18px; padding: 32px;
  box-shadow: 0 4px 32px rgba(10,22,40,.07);
}
.form-group { margin-bottom: 18px; }
label.lbl {
  display: block; font-size: .73rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .07em;
  color: var(--muted); margin-bottom: 7px;
}
input[type=email], input[type=password], input[type=text], select, textarea {
  width: 100%; padding: 11px 14px; border-radius: 11px;
  border: 1.5px solid #d8e4f5; background: #f7faff;
  font-family: var(--font); font-size: .9rem; color: var(--navy);
  outline: none; transition: border-color .18s, box-shadow .18s, background .18s;
}
input:focus, select:focus, textarea:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px rgba(26,111,232,.1);
  background: #fff;
}
input::placeholder, textarea::placeholder { color: #aab6cc; }
.btn-submit {
  width: 100%; padding: 13px; border-radius: 11px;
  background: var(--blue); color: #fff;
  font-family: var(--font); font-size: .95rem; font-weight: 600;
  border: none; cursor: pointer;
  box-shadow: 0 6px 18px rgba(26,111,232,.28);
  transition: all .18s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit:hover { background: var(--blue2); transform: translateY(-1px); box-shadow: 0 8px 22px rgba(26,111,232,.35); }
.alert {
  padding: 11px 15px; border-radius: 10px; font-size: .85rem;
  display: flex; align-items: center; gap: 8px; margin-bottom: 18px;
}
.alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
.auth-foot { text-align: center; margin-top: 18px; font-size: .875rem; color: var(--muted); }
.auth-foot a { color: var(--blue); font-weight: 600; text-decoration: none; }
.auth-foot a:hover { text-decoration: underline; }
.divider { height: 1px; background: var(--border); margin: 20px 0; }

/* FOOTER */
footer {
  background: var(--navy); color: rgba(255,255,255,.5);
  text-align: center; padding: 18px 24px; font-size: .8rem;
}
footer a { color: rgba(255,255,255,.7); text-decoration: none; }
footer a:hover { color: #fff; }
</style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <a class="brand" href="index.php">
      <div class="brand-icon"><i class="fas fa-heartbeat"></i></div>
      Afya<span>Bora</span>
    </a>
    <a href="index.php"    class="nav-link">Home</a>
    <a href="about.php"    class="nav-link">About</a>
    <a href="contact.php"  class="nav-link">Contact</a>
    <a href="register.php" class="btn-nav btn-solid" style="margin-left:6px">Register</a>
  </div>
</nav>

<main>
  <div class="auth-wrap">
    <div class="auth-icon-wrap">
      <div class="auth-icon"><i class="fas fa-heartbeat"></i></div>
      <div class="auth-title">Welcome back</div>
      <div class="auth-sub">Sign in to your AfyaBora account</div>
    </div>

    <div class="card">
      <?php if (isset($error)): ?>
      <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success"><i class="fas fa-circle-check"></i> Account created successfully. Please log in.</div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label class="lbl">Email Address</label>
          <input type="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group" style="margin-bottom:24px">
          <label class="lbl">Password</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-submit">
          <i class="fas fa-arrow-right-to-bracket"></i> Sign In
        </button>
      </form>

      <div class="divider"></div>
      <div class="auth-foot">Don't have an account? <a href="register.php">Create one</a></div>
    </div>
  </div>
</main>

<footer>
  <p>&copy; <?= date("Y") ?> AfyaBora Outpatient System &nbsp;·&nbsp;
     <a href="about.php">About</a> &nbsp;·&nbsp; <a href="contact.php">Contact</a></p>
</footer>

</body>
</html>