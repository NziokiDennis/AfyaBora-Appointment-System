<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — AfyaBora</title>
</head>
<body>
<?php include "partials/navbar.php"; ?>

<div class="af-page">

  <section class="af-hero">
    <div class="af-hero-inner">
      <div class="badge-pill"><i class="fas fa-envelope"></i> Contact</div>
      <h1>Get in Touch</h1>
      <p>Have a question, need support, or want to partner with us? We're here to help.</p>
    </div>
  </section>

  <section style="padding:56px 24px">
    <div class="af-container">
      <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:28px;align-items:start">

        <!-- Contact info -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <?php
          $info = [
            ['fas fa-envelope','Email','support@afyaboraclinic.com','mailto:support@afyaboraclinic.com'],
            ['fas fa-phone','Phone','+254 702 129 493','tel:+254702129493'],
            ['fas fa-location-dot','Address','123 AfyaCentre Street, Nairobi, Kenya',null],
            ['fas fa-clock','Hours','Mon–Fri: 8am – 6pm | Sat: 9am – 1pm',null],
          ];
          foreach ($info as [$icon,$label,$val,$href]): ?>
          <div class="af-card" style="display:flex;align-items:flex-start;gap:14px;padding:18px 20px">
            <div style="width:38px;height:38px;border-radius:9px;background:#e8f2ff;display:flex;align-items:center;justify-content:center;color:var(--blue);flex-shrink:0">
              <i class="<?= $icon ?>"></i>
            </div>
            <div>
              <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:3px"><?= $label ?></div>
              <?php if($href): ?>
              <a href="<?= $href ?>" style="color:var(--navy);font-size:.88rem;font-weight:500;text-decoration:none"><?= $val ?></a>
              <?php else: ?>
              <span style="color:var(--navy);font-size:.88rem;font-weight:500"><?= $val ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Form -->
        <div class="af-card">
          <h3 style="font-family:var(--font-d);font-size:1.1rem;margin-bottom:20px">Send a Message</h3>
          <?php if(isset($_GET['sent'])): ?>
          <div class="af-alert af-alert-success"><i class="fas fa-circle-check"></i> Message sent successfully.</div>
          <?php endif; ?>
          <form action="contact_process.php" method="POST">
            <div class="af-form-group">
              <label class="af-label">Full Name</label>
              <input type="text" name="name" class="af-input" placeholder="Your full name" required>
            </div>
            <div class="af-form-group">
              <label class="af-label">Email Address</label>
              <input type="email" name="email" class="af-input" placeholder="you@example.com" required>
            </div>
            <div class="af-form-group">
              <label class="af-label">Message</label>
              <textarea name="message" class="af-textarea" placeholder="How can we help you?" required></textarea>
            </div>
            <button type="submit" class="af-btn af-btn-solid" style="width:100%;justify-content:center;padding:12px">
              <i class="fas fa-paper-plane"></i> Send Message
            </button>
          </form>
        </div>

      </div>
    </div>
  </section>

</div>

<?php include "partials/footer.php"; ?>
</body>
</html>