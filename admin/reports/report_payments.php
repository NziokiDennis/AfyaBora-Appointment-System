<?php
require_once "../admin_auth.php";
require_once "../../config/db.php";

$admin_name   = $_SESSION["full_name"] ?? "Admin";
$current_page = 'reports';

$sc = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'")->fetch_assoc();
$scheduled_count = (int)$sc['c'];

$nr = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read = 0");
$notif_count = (int)$nr->fetch_assoc()['c'];

// Summary stats
$total_revenue   = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE payment_status='paid'")->fetch_assoc()['t'];
$total_pending   = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE payment_status='pending'")->fetch_assoc()['t'];
$paid_count      = $conn->query("SELECT COUNT(*) AS c FROM payments WHERE payment_status='paid'")->fetch_assoc()['c'];
$pending_count   = $conn->query("SELECT COUNT(*) AS c FROM payments WHERE payment_status='pending'")->fetch_assoc()['c'];
$unpaid_count    = $conn->query("SELECT COUNT(*) AS c FROM appointments a LEFT JOIN payments p ON a.appointment_id=p.appointment_id WHERE p.appointment_id IS NULL OR p.payment_status='unpaid'")->fetch_assoc()['c'];

// Monthly revenue for chart
$monthly_res = $conn->query("
    SELECT MONTHNAME(payment_date) AS mon, MONTH(payment_date) AS m_num, COALESCE(SUM(amount),0) AS total
    FROM payments
    WHERE payment_status='paid' AND YEAR(payment_date)=YEAR(CURDATE())
    GROUP BY m_num, mon ORDER BY m_num
");
$chart_labels = []; $chart_data = [];
if($monthly_res) {
    while($row = $monthly_res->fetch_assoc()){
        $chart_labels[] = $row['mon'];
        $chart_data[]   = (float)$row['total'];
    }
}

// Recent payments
$recent_payments = $conn->query("
    SELECT
        p.payment_id,
        p.amount,
        p.payment_status,
        p.payment_date,
        p.payment_method,
        u.full_name AS patient_name,
        d.full_name AS doctor_name,
        a.appointment_date
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.appointment_id
    JOIN patients pt ON a.patient_id = pt.patient_id
    JOIN users u ON pt.user_id = u.user_id
    JOIN users d ON a.doctor_id = d.user_id
    ORDER BY p.payment_date DESC
    LIMIT 30
")->fetch_all(MYSQLI_ASSOC);

// Status breakdown for doughnut
$status_res = $conn->query("SELECT payment_status, COUNT(*) AS c, COALESCE(SUM(amount),0) AS total FROM payments GROUP BY payment_status");
$status_labels=[]; $status_counts=[]; $status_amounts=[];
if($status_res){ while($row=$status_res->fetch_assoc()){
    $status_labels[] = ucfirst($row['payment_status']);
    $status_counts[] = (int)$row['c'];
    $status_amounts[]= (float)$row['total'];
}}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments Report — HealthAdmin</title>
<?php include "../sidebar.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="main-wrap">
  <header class="topbar">
    <div>
      <div class="topbar-title">Payments & Revenue</div>
      <div class="topbar-crumb"><a href="../dashboard.php">Home</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> <a href="reports_dashboard.php">Reports</a> <i class="fas fa-chevron-right" style="font-size:.55rem"></i> Payments</div>
    </div>
    <div class="topbar-right">
      <a href="reports_dashboard.php" class="ha-btn ha-btn-ghost ha-btn-sm"><i class="fas fa-arrow-left"></i> All Reports</a>
      <a href="../notifications.php" class="topbar-icon-btn"><i class="fas fa-bell"></i><?php if($notif_count>0):?><span class="notif-dot"></span><?php endif;?></a>
    </div>
  </header>

  <main class="page-content">
    <div class="page-header">
      <h2><i class="fas fa-money-bill-wave"></i> Payments & Revenue</h2>
      <p>Financial overview, payment status breakdown and revenue trends.</p>
    </div>

    <div class="mini-stats">
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-circle-check"></i></div><div><div class="mini-stat-val" style="font-size:1.1rem">KES <?= number_format($total_revenue,2) ?></div><div class="mini-stat-lbl">Collected Revenue</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon amber"><i class="fas fa-clock"></i></div><div><div class="mini-stat-val" style="font-size:1.1rem">KES <?= number_format($total_pending,2) ?></div><div class="mini-stat-lbl">Pending</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon green"><i class="fas fa-receipt"></i></div><div><div class="mini-stat-val"><?= $paid_count ?></div><div class="mini-stat-lbl">Paid Transactions</div></div></div>
      <div class="mini-stat"><div class="mini-stat-icon rose"><i class="fas fa-file-invoice"></i></div><div><div class="mini-stat-val"><?= $unpaid_count ?></div><div class="mini-stat-lbl">Unpaid</div></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px"><i class="fas fa-chart-line" style="color:var(--teal)"></i> Monthly Revenue (<?= date('Y') ?>)</div>
        <canvas id="revenueChart" style="max-height:220px"></canvas>
      </div>
      <div class="ha-card">
        <div style="font-size:.82rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:7px"><i class="fas fa-chart-pie" style="color:var(--teal)"></i> Payment Status</div>
        <canvas id="statusChart" style="max-height:220px"></canvas>
      </div>
    </div>

    <div class="ha-card" style="padding:0;overflow:hidden">
      <div style="padding:18px 20px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <i class="fas fa-list" style="color:var(--teal)"></i> Recent Transactions (last 30)
      </div>
      <div style="overflow-x:auto">
      <table class="ha-table">
        <thead>
          <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Appt Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Paid On</th></tr>
        </thead>
        <tbody>
          <?php if(empty($recent_payments)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px">No payment records found.</td></tr>
          <?php else: foreach($recent_payments as $i=>$p): ?>
          <tr>
            <td style="color:var(--muted);font-size:.72rem"><?= $i+1 ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($p['patient_name']) ?></td>
            <td style="color:var(--teal)"><?= htmlspecialchars($p['doctor_name']) ?></td>
            <td><?= $p['appointment_date'] ?></td>
            <td style="font-family:var(--font-mono);font-weight:700">KES <?= number_format($p['amount'],2) ?></td>
            <td style="text-transform:capitalize;color:var(--muted)"><?= htmlspecialchars($p['payment_method'] ?? '—') ?></td>
            <td><span class="ha-badge badge-<?= strtolower($p['payment_status']) ?>"><?= ucfirst($p['payment_status']) ?></span></td>
            <td style="color:var(--muted)"><?= $p['payment_date'] ?? '—' ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    </div>
  </main>
</div>

<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = 'Plus Jakarta Sans';

// Revenue line
const rctx = document.getElementById('revenueChart').getContext('2d');
const rGrad = rctx.createLinearGradient(0,0,0,200);
rGrad.addColorStop(0,'rgba(52,201,125,.3)');
rGrad.addColorStop(1,'rgba(52,201,125,0)');
new Chart(rctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Revenue (KES)',
      data: <?= json_encode($chart_data) ?>,
      fill: true, backgroundColor: rGrad,
      borderColor: '#34c97d', borderWidth: 2.5,
      tension: 0.4, pointBackgroundColor: '#34c97d', pointRadius: 4
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

// Status doughnut
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($status_labels) ?>,
    datasets: [{ data: <?= json_encode($status_counts) ?>, backgroundColor: ['#34c97d','#f5a623','#f05b70','#3b7cff'], hoverOffset: 6 }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { padding: 14, boxWidth: 12 } } } }
});
</script>
</body>
</html>