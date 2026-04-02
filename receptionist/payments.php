<?php
require_once "../config/auth.php";
checkRole("receptionist");
require_once "../config/db.php";

$message = "";
$error = "";
$receptionist_id = $_SESSION["user_id"];
$status_filter = $_GET["status"] ?? "all";
$search = trim($_GET["search"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointment_id = (int)($_POST["appointment_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($appointment_id && in_array($action, ["confirm", "reject"], true)) {
        if ($action === "confirm") {
            $stmt = $conn->prepare("
                UPDATE appointments
                SET payment_status = 'paid',
                    payment_date = NOW(),
                    updated_at = NOW(),
                    updated_by = ?
                WHERE appointment_id = ? AND payment_status = 'pending'
            ");
            $stmt->bind_param("ii", $receptionist_id, $appointment_id);
            $message = $stmt->execute() ? "Payment confirmed successfully." : "Could not confirm payment.";
        } else {
            $stmt = $conn->prepare("
                UPDATE appointments
                SET payment_status = 'unpaid',
                    payment_method = NULL,
                    payment_reference = NULL,
                    payment_date = NULL,
                    updated_at = NOW(),
                    updated_by = ?
                WHERE appointment_id = ? AND payment_status = 'pending'
            ");
            $stmt->bind_param("ii", $receptionist_id, $appointment_id);
            $error = $stmt->execute() ? "Payment submission was rejected and reset to unpaid." : "Could not reject payment.";
        }
    }
}

$counts = [
    "all" => 0,
    "pending" => 0,
    "unpaid" => 0,
    "paid" => 0,
];
$countRes = $conn->query("
    SELECT
        CASE
            WHEN payment_status = 'paid' THEN 'paid'
            WHEN payment_status = 'pending' AND payment_reference IS NOT NULL AND payment_reference != '' THEN 'pending'
            ELSE 'unpaid'
        END AS derived_status,
        COUNT(*) AS c
    FROM appointments
    WHERE status = 'scheduled'
    GROUP BY derived_status
");
if ($countRes) {
    while ($countRow = $countRes->fetch_assoc()) {
        $key = $countRow["derived_status"] ?: "unpaid";
        $counts[$key] = (int)$countRow["c"];
        $counts["all"] += (int)$countRow["c"];
    }
}

$where = ["a.status = 'scheduled'"];
$params = [];
$types = "";

if ($status_filter === "paid") {
    $where[] = "a.payment_status = 'paid'";
} elseif ($status_filter === "pending") {
    $where[] = "a.payment_status = 'pending' AND a.payment_reference IS NOT NULL AND a.payment_reference != ''";
} elseif ($status_filter === "unpaid") {
    $where[] = "(a.payment_status IS NULL OR a.payment_status = 'unpaid' OR (a.payment_status = 'pending' AND (a.payment_reference IS NULL OR a.payment_reference = '')))";
}
if ($status_filter === "paid" || $status_filter === "pending" || $status_filter === "unpaid") {
    if ($status_filter === "paid" || $status_filter === "pending" || $status_filter === "unpaid") {
        // filter added inline above for compatibility with older schemas
    }
}

if ($search !== "") {
    $where[] = "(puser.full_name LIKE ? OR d.full_name LIKE ? OR a.payment_reference LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql = "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.payment_status, a.payment_amount,
           a.payment_method, a.payment_reference, a.payment_date,
           puser.full_name AS patient_name, d.full_name AS doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users puser ON p.user_id = puser.user_id
    JOIN users d ON a.doctor_id = d.user_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY FIELD(a.payment_status, 'pending', 'unpaid', 'paid'), a.appointment_date ASC, a.appointment_time ASC
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Payments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0a1628;
            --blue: #1a6fe8;
            --blue2: #1259c4;
            --sky: #e8f2ff;
            --white: #ffffff;
            --muted: #6b7a99;
            --border: rgba(26,111,232,0.14);
        }
        body {
            background:
                radial-gradient(circle at top left, rgba(26,111,232,.10), transparent 30%),
                linear-gradient(180deg, #f4f8ff 0%, #edf4ff 100%);
            color: var(--navy);
        }
        .page-shell,
        .payment-table-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(10,22,40,.08);
        }
        .page-shell {
            padding: 28px;
            margin-bottom: 24px;
        }
        .payment-table-card {
            overflow: hidden;
        }
        .search-shell {
            background: #f7fbff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 18px;
        }
        .stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--navy);
            text-decoration: none;
            font-weight: 600;
            transition: all .18s ease;
        }
        .stat-pill:hover,
        .stat-pill.active {
            background: linear-gradient(135deg, #1a6fe8, #1259c4);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 10px 18px rgba(26,111,232,.18);
        }
        .stat-pill .count {
            background: rgba(26,111,232,.1);
            color: var(--blue2);
            border-radius: 999px;
            padding: 2px 9px;
            font-size: .82rem;
        }
        .stat-pill.active .count,
        .stat-pill:hover .count {
            background: rgba(255,255,255,.18);
            color: #fff;
        }
        .search-input {
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 11px 14px;
        }
        .search-input:focus {
            border-color: #1a6fe8;
            box-shadow: 0 0 0 3px rgba(26,111,232,.12);
        }
        .table thead th {
            background: #f7fbff;
            color: var(--muted);
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-bottom: 1px solid var(--border);
        }
        .table tbody td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include "navbar.php"; ?>
    <div class="container mt-5">
        <div class="page-shell d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="mb-1">Payment Desk</h2>
                <p class="text-muted mb-0">Confirm patient payments so appointments become fully confirmed.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-primary" style="border-radius:12px;">Back to Dashboard</a>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="search-shell">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php
                $filters = [
                    "all" => "All Payments",
                    "pending" => "Pending",
                    "unpaid" => "Unpaid",
                    "paid" => "Paid",
                ];
                foreach ($filters as $key => $label):
                    $query = ["status" => $key];
                    if ($search !== "") {
                        $query["search"] = $search;
                    }
                    $active = $status_filter === $key ? "active" : "";
                ?>
                    <a href="?<?= http_build_query($query) ?>" class="stat-pill <?= $active ?>">
                        <span><?= $label ?></span>
                        <span class="count"><?= $counts[$key] ?? 0 ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                <div class="col-md-8">
                    <input
                        type="text"
                        name="search"
                        class="form-control search-input"
                        placeholder="Search patient, doctor, or payment reference..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1" style="border-radius:12px;background:linear-gradient(135deg,#1a6fe8,#1259c4);border:none;">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                    <?php if ($search !== "" || $status_filter !== "all"): ?>
                        <a href="payments.php" class="btn btn-outline-secondary" style="border-radius:12px;">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="payment-table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Appointment</th>
                                <th>Payment</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($rows && $rows->num_rows > 0): ?>
                            <?php while ($row = $rows->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row["patient_name"]) ?></td>
                                    <td>Dr. <?= htmlspecialchars($row["doctor_name"]) ?></td>
                                    <td>
                                        <?= date("D, M j, Y", strtotime($row["appointment_date"])) ?><br>
                                        <small class="text-muted"><?= date("g:i A", strtotime($row["appointment_time"])) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $derived_status = "unpaid";
                                        if ($row["payment_status"] === "paid") {
                                            $derived_status = "paid";
                                        } elseif ($row["payment_status"] === "pending" && !empty($row["payment_reference"])) {
                                            $derived_status = "pending";
                                        }
                                        ?>
                                        <?php if ($derived_status === "paid"): ?>
                                            <span class="badge bg-success">Paid</span>
                                            <div class="small text-muted"><?= $row["payment_date"] ? date("d M Y, g:i A", strtotime($row["payment_date"])) : "" ?></div>
                                        <?php elseif ($derived_status === "pending"): ?>
                                            <span class="badge bg-info text-dark">Pending Confirmation</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row["payment_reference"] ?: "N/A") ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($row["payment_method"] ?: "No method submitted") ?></small>
                                    </td>
                                    <td>KSh <?= number_format($row["payment_amount"], 2) ?></td>
                                    <td>
                                        <?php if ($derived_status === "pending"): ?>
                                            <form method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$row["appointment_id"] ?>">
                                                <button name="action" value="confirm" class="btn btn-sm btn-success">Confirm</button>
                                                <button name="action" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this payment submission?');">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">No action</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No appointment payments matched this view.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include "../partials/footer.php"; ?>
</body>
</html>
