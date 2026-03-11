<?php
require_once "config/db.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];
    $phone     = trim($_POST["phone_number"]);
    $role      = $_POST["role"];
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = "All required fields must be filled.";
    } elseif (!in_array($role, ["doctor","patient"])) {
        $error = "Invalid role.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name,email,password_hash,phone_number,role) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $full_name, $email, $hash, $phone, $role);
        if ($stmt->execute()) { header("Location: login.php?registered=true"); exit; }
        else { $error = "Email may already be registered."; }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — AfyaBora</title>
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
body { font-family: var(--font); background: #f0f6ff; color: var(--navy); min-height: 100vh; display: flex; flex-direction: column; }
nav { background: var(--white); border-bottom: 1px solid var(--border); box-shadow: 0 2px 16px rgba(10,22,40,0.06); position: sticky; top: 0; z-index: 100; }
.nav-inner { max-width: 1100px; margin: 0 auto; padding: 0 28px; height: 64px; display: flex; align-items: center; gap: 6px; }
.brand { font-family: var(--font-d); font-size: 1.05rem; font-weight: 700; color: var(--navy); text-decoration: none; display: flex; align-items: center; gap: 9px; margin-right: auto; }
.brand-icon { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, var(--blue), var(--blue2)); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .85rem; box-shadow: 0 4px 12px rgba(26,111,232,.28); }
.brand span { color: var(--blue); }
.nav-link { font-size: .875rem; font-weight: 500; color: var(--muted); text-decoration: none; padding: 7px 13px; border-radius: 8px; transition: all .18s; }
.nav-link:hover { color: var(--navy); background: var(--sky); }
.nav-link.active { color: var(--blue); background: var(--sky); font-weight: 600; }
.btn-nav { padding: 7px 18px; border-radius: 9px; font-size: .875rem; font-weight: 600; text-decoration: none; transition: all .18s; display: inline-flex; align-items: center; gap: 6px; margin-left: 4px; }
.btn-outline { color: var(--blue); border: 1.5px solid var(--blue); background: transparent; }
.btn-outline:hover { background: var(--sky); }
.btn-solid { background: var(--blue); color: #fff; border: 1.5px solid var(--blue); box-shadow: 0 4px 14px rgba(26,111,232,.22); }
.btn-solid:hover { background: var(--blue2); }
main { flex: 1; }
footer { background: var(--navy); color: rgba(255,255,255,.5); text-align: center; padding: 18px 24px; font-size: .8rem; }
footer a { color: rgba(255,255,255,.7); text-decoration: none; }
footer a:hover { color: #fff; }
.container { max-width: 1100px; margin: 0 auto; padding: 0 28px; }
.hero { background: linear-gradient(135deg, var(--navy) 0%, #0d2a5e 100%); color: #fff; padding: 80px 28px; position: relative; overflow: hidden; }
.hero::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse at 75% 50%, rgba(26,111,232,.22) 0%, transparent 65%); pointer-events: none; }
.hero-inner { max-width: 1100px; margin: 0 auto; position: relative; }
.hero-pill { display: inline-flex; align-items: center; gap: 7px; background: rgba(26,111,232,.2); border: 1px solid rgba(26,111,232,.4); color: #93c5fd; font-size: .72rem; font-weight: 700; padding: 4px 13px; border-radius: 99px; margin-bottom: 18px; text-transform: uppercase; letter-spacing: .08em; }
.hero h1 { font-family: var(--font-d); font-size: clamp(1.8rem,4vw,2.75rem); font-weight: 700; line-height: 1.2; margin-bottom: 14px; }
.hero p { font-size: 1.05rem; color: rgba(255,255,255,.7); max-width: 540px; line-height: 1.7; }
.section { padding: 64px 28px; }
.card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: 0 2px 20px rgba(10,22,40,.06); }
.icon-box { width: 44px; height: 44px; border-radius: 11px; background: var(--sky); display: flex; align-items: center; justify-content: center; color: var(--blue); font-size: 1rem; flex-shrink: 0; }
.form-group { margin-bottom: 18px; }
label.lbl { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); margin-bottom: 7px; }
input[type=email], input[type=password], input[type=text], select, textarea { width: 100%; padding: 11px 14px; border-radius: 11px; border: 1.5px solid #d8e4f5; background: #f7faff; font-family: var(--font); font-size: .9rem; color: var(--navy); outline: none; transition: border-color .18s, box-shadow .18s, background .18s; }
input:focus, select:focus, textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(26,111,232,.1); background: #fff; }
input::placeholder, textarea::placeholder { color: #aab6cc; }
textarea { resize: vertical; min-height: 110px; }
.btn-submit { width: 100%; padding: 13px; border-radius: 11px; background: var(--blue); color: #fff; font-family: var(--font); font-size: .95rem; font-weight: 600; border: none; cursor: pointer; box-shadow: 0 6px 18px rgba(26,111,232,.26); transition: all .18s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-submit:hover { background: var(--blue2); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(26,111,232,.35); }
.alert { padding: 11px 15px; border-radius: 10px; font-size: .85rem; display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
.alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
.section-title { font-family: var(--font-d); font-size: 1.5rem; font-weight: 700; text-align: center; margin-bottom: 8px; }
.section-sub { color: var(--muted); font-size: .9rem; text-align: center; margin-bottom: 36px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 18px; }
@media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<?php session_start(); ?>
<nav>
  <div class="nav-inner">
    <a class="brand" href="index.php">
      <div class="brand-icon"><i class="fas fa-heartbeat"></i></div>
      Afya<span>Bora</span>
    </a>
    <a href="index.php"   class="nav-link ">Home</a>
    <a href="about.php"   class="nav-link ">About</a>
    <a href="contact.php" class="nav-link ">Contact</a>
    <?php if (isset($_SESSION["user_id"])): ?>
      <a href="logout.php" class="btn-nav btn-outline">Sign Out</a>
    <?php else: ?>
      <a href="login.php"    class="btn-nav btn-outline">Login</a>
      <a href="register.php" class="btn-nav btn-solid">Register</a>
    <?php endif; ?>
  </div>
</nav>
<main style="display:flex;align-items:center;justify-content:center;padding:48px 24px">
  <div style="width:100%;max-width:500px">
    <div style="text-align:center;margin-bottom:28px">
      <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,var(--blue),var(--blue2));display:inline-flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;box-shadow:0 8px 24px rgba(26,111,232,.3);margin-bottom:14px">
        <i class="fas fa-user-plus"></i>
      </div>
      <h1 style="font-family:var(--font-d);font-size:1.6rem;font-weight:700;margin-bottom:6px">Create an account</h1>
      <p style="color:var(--muted);font-size:.9rem">Join AfyaBora Outpatient System</p>
    </div>
    <div class="card">
      <?php if (isset($error)): ?>
      <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" action="register.php">
        <div class="form-group">
          <label class="lbl">Full Name <span style="color:#dc2626">*</span></label>
          <input type="text" name="full_name" placeholder="John Doe" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="form-group">
            <label class="lbl">Email <span style="color:#dc2626">*</span></label>
            <input type="email" name="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="lbl">Phone</label>
            <input type="text" name="phone_number" placeholder="07XXXXXXXX" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="lbl">Password <span style="color:#dc2626">*</span></label>
          <input type="password" name="password" placeholder="Minimum 6 characters" required>
        </div>
        <div class="form-group" style="margin-bottom:24px">
          <label class="lbl">I am a <span style="color:#dc2626">*</span></label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:6px">
            <label class="role-opt" id="opt-patient" style="display:flex;align-items:center;gap:10px;padding:13px 16px;border-radius:11px;border:1.5px solid #d8e4f5;cursor:pointer;transition:all .15s;background:#f7faff">
              <input type="radio" name="role" value="patient" style="display:none" onchange="pickRole(this)">
              <div style="width:36px;height:36px;border-radius:9px;background:var(--sky);display:flex;align-items:center;justify-content:center;color:var(--blue)"><i class="fas fa-user"></i></div>
              <div>
                <div style="font-weight:600;font-size:.88rem">Patient</div>
                <div style="font-size:.75rem;color:var(--muted)">Book appointments</div>
              </div>
            </label>
            <label class="role-opt" id="opt-doctor" style="display:flex;align-items:center;gap:10px;padding:13px 16px;border-radius:11px;border:1.5px solid #d8e4f5;cursor:pointer;transition:all .15s;background:#f7faff">
              <input type="radio" name="role" value="doctor" style="display:none" onchange="pickRole(this)">
              <div style="width:36px;height:36px;border-radius:9px;background:var(--sky);display:flex;align-items:center;justify-content:center;color:var(--blue)"><i class="fas fa-user-md"></i></div>
              <div>
                <div style="font-weight:600;font-size:.88rem">Doctor</div>
                <div style="font-size:.75rem;color:var(--muted)">Manage patients</div>
              </div>
            </label>
          </div>
        </div>
        <button type="submit" class="btn-submit"><i class="fas fa-user-plus"></i> Create Account</button>
      </form>
      <p style="text-align:center;margin-top:18px;font-size:.875rem;color:var(--muted)">
        Already have an account? <a href="login.php" style="color:var(--blue);font-weight:600;text-decoration:none">Sign In</a>
      </p>
    </div>
  </div>
</main>
<footer>
  <p>&copy; <?= date("Y") ?> AfyaBora Outpatient System &nbsp;·&nbsp;
     <a href="about.php">About</a> &nbsp;·&nbsp; <a href="contact.php">Contact</a></p>
</footer>
<script>
function pickRole(radio) {
  document.querySelectorAll('.role-opt').forEach(el => {
    el.style.borderColor = '#d8e4f5';
    el.style.background  = '#f7faff';
  });
  const opt = document.getElementById('opt-' + radio.value);
  opt.style.borderColor = 'var(--blue)';
  opt.style.background  = 'var(--sky)';
}
</script>
</body>
</html>