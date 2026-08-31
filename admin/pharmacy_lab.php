<?php
require_once "admin_auth.php";
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_OFF);

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'pharmacy_lab';
$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];
$notif_count = (int)($conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read=0")->fetch_assoc()['c'] ?? 0);

$total_lab   = (int)($conn->query("SELECT COUNT(*) AS c FROM lab_results")->fetch_assoc()['c'] ?? 0);
$total_disp  = (int)($conn->query("SELECT COUNT(*) AS c FROM pharmacy_dispenses")->fetch_assoc()['c'] ?? 0);

$lab_sql = "
    SELECT lr.test_name, lr.result_value, lr.normal_range, lr.created_at,
           puser.full_name AS patient_name, d.full_name AS doctor_name
    FROM lab_results lr
    JOIN appointments a ON lr.appointment_id = a.appointment_id
    JOIN users puser ON a.patient_id = puser.user_id
    JOIN users d ON a.doctor_id = d.user_id
    ORDER BY lr.created_at DESC
    LIMIT 50
";
$lab_results = $conn->query($lab_sql);

$disp_sql = "
    SELECT pd.medication_name, pd.dosage, pd.quantity, pd.dispensed_at,
           puser.full_name AS patient_name, u.full_name AS dispensed_by_name
    FROM pharmacy_dispenses pd
    JOIN appointments a ON pd.appointment_id = a.appointment_id
    JOIN users puser ON a.patient_id = puser.user_id
    LEFT JOIN users u ON pd.dispensed_by = u.user_id
    ORDER BY pd.dispensed_at DESC
    LIMIT 50
";
$dispenses = $conn->query($disp_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pharmacy &amp; Lab — HealthAdmin</title>
</head>
<body>
<?php include "sidebar.php"; ?>

<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Pharmacy &amp; Lab</div>
      <div class="topbar-crumb"><a href="dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Pharmacy &amp; Lab</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-chip"><i class="fas fa-calendar-alt" style="color:var(--teal)"></i><?php echo date("D, M j Y"); ?></div>
      <a href="notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
      <a href="settings.php" class="topbar-icon-btn"><i class="fas fa-cog"></i></a>
    </div>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h2><i class="fas fa-flask"></i> Pharmacy &amp; Lab Activity</h2>
      <p>Read-only oversight of lab results recorded by doctors and medication dispensed by reception.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon blue"><i class="fas fa-vial"></i></div><div><div class="mini-stat-val"><?= $total_lab ?></div><div class="mini-stat-lbl">Lab Results Recorded</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon teal"><i class="fas fa-pills"></i></div><div><div class="mini-stat-val"><?= $total_disp ?></div><div class="mini-stat-lbl">Medications Dispensed</div></div></div>
    </div>

    <div class="page-header" style="margin-top:24px">
      <h2 style="font-size:1.1rem"><i class="fas fa-vial"></i> Recent Lab Results</h2>
    </div>
    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="overflow-x:auto">
      <table class="ha-table">
        <thead>
          <tr>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Test</th>
            <th>Result</th>
            <th>Normal Range</th>
            <th>Recorded</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$lab_results || $lab_results->num_rows === 0): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px">No lab results recorded yet.</td></tr>
          <?php else: while ($lr = $lab_results->fetch_assoc()): ?>
          <tr>
            <td style="font-weight:600"><?= htmlspecialchars($lr['patient_name']) ?></td>
            <td>Dr. <?= htmlspecialchars($lr['doctor_name']) ?></td>
            <td><?= htmlspecialchars($lr['test_name']) ?></td>
            <td><?= htmlspecialchars($lr['result_value']) ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($lr['normal_range'] ?: '—') ?></td>
            <td style="color:var(--muted);font-size:.75rem"><?= date('M j, Y g:i A', strtotime($lr['created_at'])) ?></td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
      </div>
    </div>

    <div class="page-header" style="margin-top:24px">
      <h2 style="font-size:1.1rem"><i class="fas fa-pills"></i> Recent Dispensed Medication</h2>
    </div>
    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="overflow-x:auto">
      <table class="ha-table">
        <thead>
          <tr>
            <th>Patient</th>
            <th>Medication</th>
            <th>Dosage</th>
            <th>Quantity</th>
            <th>Dispensed By</th>
            <th>Dispensed</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$dispenses || $dispenses->num_rows === 0): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px">No dispensing records yet.</td></tr>
          <?php else: while ($pd = $dispenses->fetch_assoc()): ?>
          <tr>
            <td style="font-weight:600"><?= htmlspecialchars($pd['patient_name']) ?></td>
            <td><?= htmlspecialchars($pd['medication_name']) ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($pd['dosage'] ?: '—') ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($pd['quantity'] ?: '—') ?></td>
            <td><?= htmlspecialchars($pd['dispensed_by_name'] ?? 'N/A') ?></td>
            <td style="color:var(--muted);font-size:.75rem"><?= date('M j, Y g:i A', strtotime($pd['dispensed_at'])) ?></td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
      </div>
    </div>

  </main>
</div>
</body>
</html>
