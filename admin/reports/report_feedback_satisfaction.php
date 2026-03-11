<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'reports';

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];
$nr = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read=0");
$notif_count = (int)$nr->fetch_assoc()['c'];

$doctors = $conn->query("
    SELECT u.full_name AS doctor_name,
           ROUND(AVG(f.rating),2) AS avg_rating,
           COUNT(f.feedback_id) AS total_feedback,
           SUM(f.rating>=4) AS positive,
           SUM(f.rating<=2) AS negative
    FROM feedback f
    JOIN users u ON f.doctor_id = u.user_id
    WHERE u.role='doctor'
    GROUP BY f.doctor_id ORDER BY avg_rating DESC
")->fetch_all(MYSQLI_ASSOC);

$dist_res = $conn->query("SELECT rating, COUNT(*) AS c FROM feedback GROUP BY rating ORDER BY rating");
$dist_labels=[]; $dist_counts=[];
if ($dist_res) while ($r=$dist_res->fetch_assoc()) { $dist_labels[]=$r['rating'].'★'; $dist_counts[]=(int)$r['c']; }

$overall_avg = $conn->query("SELECT ROUND(AVG(rating),2) AS r FROM feedback")->fetch_assoc()['r'] ?? 0;
$total_feedback = $conn->query("SELECT COUNT(*) AS c FROM feedback")->fetch_assoc()['c'];

// Recent feedback with comments
$recent = $conn->query("
    SELECT f.rating, f.comments, f.created_at,
           u.full_name AS patient_name,
           d.full_name AS doctor_name
    FROM feedback f
    JOIN patients p ON f.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    JOIN users d ON f.doctor_id = d.user_id
    WHERE f.comments IS NOT NULL AND f.comments != ''
    ORDER BY f.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback & Satisfaction — HealthAdmin</title>
<style>
.stars { color: var(--amber); letter-spacing: 1px; font-size: .85rem; }
.feedback-card { background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; }
.feedback-card .rating-row { display:flex;align-items:center;gap:10px;margin-bottom:6px; }
</style>
</head>
<body>
<?php include "../sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Feedback & Satisfaction</div>
      <div class="topbar-crumb">
        <a href="../dashboard.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        <a href="reports_dashboard.php">Reports</a>
        <i class="fas fa-chevron-right" style="font-size:.55rem"></i>
        Feedback
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?= date("D, M j Y") ?></div>
      <a href="../notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="reports_dashboard.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-arrow-left"></i> Reports</a>
    </div>
  </header>

  <main class="page-content">
    <div class="page-header">
      <h2><i class="fas fa-star"></i> Feedback & Satisfaction</h2>
      <p>Patient ratings, doctor scores, and satisfaction trends.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-star"></i></div><div><div class="mini-stat-val"><?= $overall_avg ?></div><div class="mini-stat-lbl">Overall Avg Rating</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-comment-dots"></i></div><div><div class="mini-stat-val"><?= $total_feedback ?></div><div class="mini-stat-lbl">Total Reviews</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-thumbs-up"></i></div><div><div class="mini-stat-val"><?= array_sum(array_column($doctors,'positive')) ?></div><div class="mini-stat-lbl">Positive (4-5★)</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-thumbs-down"></i></div><div><div class="mini-stat-val"><?= array_sum(array_column($doctors,'negative')) ?></div><div class="mini-stat-lbl">Negative (1-2★)</div></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-bar" style="color:var(--amber)"></i> Doctor Rating Comparison
        </div>
        <canvas id="doctorChart" style="max-height:240px"></canvas>
      </div>
      <div class="ha-card" style="display:flex;flex-direction:column">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-chart-pie" style="color:var(--teal)"></i> Rating Distribution
        </div>
        <div style="flex:1;display:flex;align-items:center;justify-content:center">
          <canvas id="distChart" style="max-height:200px"></canvas>
        </div>
      </div>
    </div>

    <!-- Doctor table -->
    <div class="ha-card" style="padding:0;overflow:hidden;margin-bottom:20px">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <i class="fas fa-user-md" style="color:var(--teal)"></i> Doctor Scores
      </div>
      <table class="ha-table">
        <thead><tr><th>Doctor</th><th>Reviews</th><th>Avg Rating</th><th>Positive</th><th>Negative</th><th>Score Bar</th></tr></thead>
        <tbody>
          <?php foreach($doctors as $d): ?>
          <tr>
            <td style="font-weight:600"><?= htmlspecialchars($d['doctor_name']) ?></td>
            <td style="font-family:var(--font-mono)"><?= $d['total_feedback'] ?></td>
            <td><span style="color:var(--amber);font-weight:700"><i class="fas fa-star" style="font-size:.7rem"></i> <?= $d['avg_rating'] ?></span></td>
            <td style="color:var(--green)"><?= $d['positive'] ?></td>
            <td style="color:var(--rose)"><?= $d['negative'] ?></td>
            <td style="width:130px">
              <div style="height:6px;border-radius:99px;background:var(--surface2)">
                <div style="height:100%;width:<?= round($d['avg_rating']/5*100) ?>%;background:var(--amber);border-radius:99px"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($recent)): ?>
    <!-- Recent comments -->
    <div class="ha-card">
      <div style="font-size:.85rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px">
        <i class="fas fa-comments" style="color:var(--teal)"></i> Recent Patient Comments
      </div>
      <?php foreach($recent as $fb): ?>
      <div class="feedback-card">
        <div class="rating-row">
          <span class="stars"><?= str_repeat('★',$fb['rating']) ?><?= str_repeat('☆',5-$fb['rating']) ?></span>
          <span style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($fb['patient_name']) ?></span>
          <span style="color:var(--muted);font-size:.72rem;margin-left:auto">→ Dr. <?= htmlspecialchars($fb['doctor_name']) ?> &nbsp;·&nbsp; <?= date('M j, Y', strtotime($fb['created_at'])) ?></span>
        </div>
        <p style="color:var(--muted);font-size:.8rem;line-height:1.5;margin:0"><?= htmlspecialchars($fb['comments']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

new Chart(document.getElementById('doctorChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($doctors,'doctor_name')) ?>,
    datasets: [
      { label: 'Avg Rating', data: <?= json_encode(array_column($doctors,'avg_rating')) ?>, backgroundColor: 'rgba(245,166,35,.8)', borderRadius: 5 },
      { label: 'Reviews',    data: <?= json_encode(array_column($doctors,'total_feedback')) ?>, backgroundColor: 'rgba(14,196,164,.6)', borderRadius: 5 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top', labels: { boxWidth: 10, padding: 14, font: { size: 11 } } } },
    scales: { y: { beginAtZero: true } }
  }
});

new Chart(document.getElementById('distChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($dist_labels) ?>,
    datasets: [{ data: <?= json_encode($dist_counts) ?>, backgroundColor: ['#f05b70','#f5a623','#6b7280','#3b7cff','#34c97d'], borderWidth: 0, hoverOffset: 6 }]
  },
  options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { padding: 12, boxWidth: 10, font: { size: 11 } } } } }
});
</script>
</body>
</html>
