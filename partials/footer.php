<style>
.af-footer {
  margin-top: auto;
  padding: 18px 24px;
  background: #0a1628;
  color: rgba(255,255,255,.72);
  text-align: center;
  width: 100%;
}
html, body {
  min-height: 100%;
}
body {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.af-footer p {
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 8px;
  text-align: center;
}
.af-footer a {
  color: #8ec5ff;
  text-decoration: none;
}
.af-footer a:hover {
  color: #ffffff;
  text-decoration: underline;
}
</style>
<?php require_once __DIR__ . "/../config/session.php"; ?>
<footer class="af-footer">
  <p>&copy; <?= date("Y") ?> AfyaBora Outpatient System <span>&middot;</span> <a href="<?= app_url('about.php') ?>">About</a> <span>&middot;</span> <a href="<?= app_url('contact.php') ?>">Contact</a></p>
</footer>
