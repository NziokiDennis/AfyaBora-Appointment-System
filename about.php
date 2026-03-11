<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — AfyaBora</title>
</head>
<body>
<?php include "partials/navbar.php"; ?>

<div class="af-page">

  <!-- Hero -->
  <section class="af-hero">
    <div class="af-hero-inner">
      <div class="badge-pill"><i class="fas fa-circle-info"></i> About Us</div>
      <h1>Simplifying Outpatient<br>Healthcare in Kenya</h1>
      <p>AfyaBora connects patients with doctors through a reliable, efficient, and secure digital system built for modern clinic management.</p>
    </div>
  </section>

  <!-- Mission / What we offer -->
  <section style="padding:64px 24px">
    <div class="af-container">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

        <div class="af-card">
          <div style="width:44px;height:44px;border-radius:11px;background:#e8f2ff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--blue);margin-bottom:16px">
            <i class="fas fa-bullseye"></i>
          </div>
          <h3 style="font-family:var(--font-d);font-size:1.15rem;margin-bottom:10px">Our Mission</h3>
          <p style="color:var(--muted);font-size:.9rem;line-height:1.7">To simplify outpatient management by providing a reliable, user-friendly, and secure digital solution that enhances doctor-patient interactions and reduces administrative overhead.</p>
        </div>

        <div class="af-card">
          <div style="width:44px;height:44px;border-radius:11px;background:#e8f2ff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--blue);margin-bottom:16px">
            <i class="fas fa-eye"></i>
          </div>
          <h3 style="font-family:var(--font-d);font-size:1.15rem;margin-bottom:10px">Our Vision</h3>
          <p style="color:var(--muted);font-size:.9rem;line-height:1.7">To become the leading outpatient management platform in East Africa, empowering healthcare providers with data-driven tools that improve patient outcomes.</p>
        </div>
      </div>

      <!-- Features -->
      <h2 style="font-family:var(--font-d);font-size:1.4rem;text-align:center;margin:52px 0 28px">What We Offer</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:18px">
        <?php
        $features = [
          ['fas fa-calendar-check', 'Online Appointments',    'Book and manage appointments anytime, from anywhere.'],
          ['fas fa-file-medical',   'Medical Records',        'Secure electronic records accessible to authorized staff.'],
          ['fas fa-shield-halved',  'Secure Data',            'End-to-end data protection with role-based access control.'],
          ['fas fa-headset',        '24/7 Support',           'Dedicated support for patients and clinic administrators.'],
          ['fas fa-chart-line',     'Analytics',              'Real-time reports and insights on clinic performance.'],
          ['fas fa-user-md',        'Doctor Profiles',        'Track doctor schedules, workloads, and patient ratings.'],
        ];
        foreach ($features as [$icon, $title, $desc]): ?>
        <div class="af-card" style="padding:22px 20px">
          <div style="width:38px;height:38px;border-radius:9px;background:#e8f2ff;display:flex;align-items:center;justify-content:center;color:var(--blue);margin-bottom:12px">
            <i class="<?= $icon ?>"></i>
          </div>
          <div style="font-weight:600;font-size:.9rem;margin-bottom:6px"><?= $title ?></div>
          <div style="color:var(--muted);font-size:.82rem;line-height:1.55"><?= $desc ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</div>

<?php include "partials/footer.php"; ?>
</body>
</html>