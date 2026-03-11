<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Sora:wght@600;700&display=swap');

:root {
  --navy:   #0a1628;
  --blue:   #1a6fe8;
  --blue2:  #1259c4;
  --sky:    #e8f2ff;
  --white:  #ffffff;
  --muted:  #6b7a99;
  --border: rgba(26,111,232,0.12);
  --font:   'DM Sans', sans-serif;
  --font-d: 'Sora', sans-serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: var(--font);
  background: #f7faff;
  color: var(--navy);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  padding-bottom: 0;
}

/* ── Navbar ── */
.af-nav {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  position: sticky; top: 0; z-index: 100;
  box-shadow: 0 2px 20px rgba(10,22,40,0.06);
}
.af-nav-inner {
  max-width: 1100px; margin: 0 auto;
  padding: 0 24px;
  height: 64px;
  display: flex; align-items: center; gap: 32px;
}
.af-brand {
  font-family: var(--font-d);
  font-size: 1.1rem; font-weight: 700;
  color: var(--navy); text-decoration: none;
  display: flex; align-items: center; gap: 9px;
  flex-shrink: 0;
}
.af-brand-icon {
  width: 34px; height: 34px; border-radius: 9px;
  background: linear-gradient(135deg, var(--blue), var(--blue2));
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; color: #fff;
  box-shadow: 0 4px 12px rgba(26,111,232,0.3);
}
.af-brand span { color: var(--blue); }

.af-links { display: flex; align-items: center; gap: 4px; margin-left: auto; }
.af-link {
  padding: 7px 14px; border-radius: 8px;
  font-size: .875rem; font-weight: 500;
  color: var(--muted); text-decoration: none;
  transition: all .18s;
}
.af-link:hover { color: var(--navy); background: var(--sky); }
.af-link.active { color: var(--blue); background: var(--sky); font-weight: 600; }

.af-btn {
  padding: 8px 18px; border-radius: 9px;
  font-size: .875rem; font-weight: 600;
  text-decoration: none; transition: all .18s;
  display: inline-flex; align-items: center; gap: 6px;
}
.af-btn-outline {
  color: var(--blue); border: 1.5px solid var(--blue);
  background: transparent;
}
.af-btn-outline:hover { background: var(--sky); }
.af-btn-solid {
  background: var(--blue); color: #fff;
  border: 1.5px solid var(--blue);
  box-shadow: 0 4px 14px rgba(26,111,232,0.25);
}
.af-btn-solid:hover { background: var(--blue2); box-shadow: 0 6px 18px rgba(26,111,232,0.35); }

.af-nav-toggle {
  display: none; background: none; border: none; cursor: pointer;
  padding: 6px; border-radius: 7px; margin-left: auto;
  color: var(--navy);
}

@media (max-width: 680px) {
  .af-links { display: none; flex-direction: column; gap: 4px; }
  .af-links.open {
    display: flex; position: absolute; top: 64px; left: 0; right: 0;
    background: var(--white); border-bottom: 1px solid var(--border);
    padding: 12px 20px 16px; box-shadow: 0 8px 24px rgba(10,22,40,.08);
  }
  .af-nav-toggle { display: flex; align-items: center; justify-content: center; }
}

/* ── Page wrapper ── */
.af-page { flex: 1; }

/* ── Footer ── */
.af-footer {
  background: var(--navy);
  color: rgba(255,255,255,0.55);
  text-align: center;
  padding: 18px 24px;
  font-size: .8rem;
}
.af-footer a { color: rgba(255,255,255,0.7); text-decoration: none; }
.af-footer a:hover { color: #fff; }

/* ── Shared page styles ── */
.af-container { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
.af-hero {
  background: linear-gradient(135deg, var(--navy) 0%, #0d2a5e 100%);
  color: #fff; padding: 72px 24px;
  position: relative; overflow: hidden;
}
.af-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at 70% 50%, rgba(26,111,232,0.25) 0%, transparent 70%);
  pointer-events: none;
}
.af-hero-inner { max-width: 1100px; margin: 0 auto; position: relative; }
.af-hero h1 { font-family: var(--font-d); font-size: clamp(1.8rem,4vw,2.8rem); font-weight: 700; line-height: 1.2; margin-bottom: 14px; }
.af-hero p  { font-size: 1.05rem; color: rgba(255,255,255,0.7); max-width: 520px; line-height: 1.65; }
.af-hero .badge-pill {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(26,111,232,0.25); border: 1px solid rgba(26,111,232,0.4);
  color: #93c5fd; font-size: .75rem; font-weight: 600;
  padding: 4px 12px; border-radius: 99px; margin-bottom: 16px;
  text-transform: uppercase; letter-spacing: .06em;
}

.af-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 16px; padding: 28px;
  box-shadow: 0 2px 16px rgba(10,22,40,.05);
}

.af-form-group { margin-bottom: 18px; }
.af-label { display: block; font-size: .78rem; font-weight: 600; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .05em; }
.af-input, .af-select, .af-textarea {
  width: 100%; padding: 11px 14px; border-radius: 10px;
  border: 1.5px solid #dde5f4; background: #f7faff;
  font-family: var(--font); font-size: .9rem; color: var(--navy);
  outline: none; transition: border-color .18s, box-shadow .18s;
}
.af-input:focus, .af-select:focus, .af-textarea:focus {
  border-color: var(--blue); box-shadow: 0 0 0 3px rgba(26,111,232,0.12);
  background: #fff;
}
.af-input::placeholder, .af-textarea::placeholder { color: #aab4cc; }
.af-textarea { resize: vertical; min-height: 110px; }

.af-alert { padding: 12px 16px; border-radius: 10px; font-size: .85rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.af-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.af-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
</style>

<nav class="af-nav">
  <div class="af-nav-inner">
    <a class="af-brand" href="index.php">
      <div class="af-brand-icon"><i class="fas fa-heartbeat"></i></div>
      Afya<span>Bora</span>
    </a>
    <button class="af-nav-toggle" onclick="this.nextElementSibling.classList.toggle('open')" aria-label="Menu">
      <i class="fas fa-bars"></i>
    </button>
    <div class="af-links">
      <a href="index.php"   class="af-link <?= $current==='index.php'  ?'active':'' ?>">Home</a>
      <a href="about.php"   class="af-link <?= $current==='about.php'  ?'active':'' ?>">About</a>
      <a href="contact.php" class="af-link <?= $current==='contact.php'?'active':'' ?>">Contact</a>
      <?php if (isset($_SESSION["user_id"])): ?>
        <a href="logout.php" class="af-btn af-btn-outline" style="margin-left:8px">Sign Out</a>
      <?php else: ?>
        <a href="login.php"    class="af-btn af-btn-outline" style="margin-left:8px">Login</a>
        <a href="register.php" class="af-btn af-btn-solid"   style="margin-left:6px">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">