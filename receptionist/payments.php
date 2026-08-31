<?php
require_once "../config/auth.php";
checkRole("receptionist");
require_once "../config/db.php";

$message = "";
$error = "";
$current_page = "payments";
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
    JOIN users d ON a.doctor_id = d.user_id AND d.role = 'doctor'
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
    <title>Payment Desk — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .filter-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .filter-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 14px; border-radius: var(--radius-pill);
            border: 1px solid var(--border); background: var(--white);
            color: var(--navy); font-size: .82rem; font-weight: 600;
            transition: all .15s;
        }
        .filter-pill .count { background: var(--sky); color: var(--blue); border-radius: var(--radius-pill); padding: 1px 8px; font-size: .74rem; }
        .filter-pill.active, .filter-pill:hover { background: var(--navy); color: #fff; border-color: var(--navy); }
        .filter-pill.active .count, .filter-pill:hover .count { background: rgba(255,255,255,.18); color: #fff; }
        .search-form { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-form input.ab-input { flex: 1; }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Payment Desk</div>
            <div class="ab-subgreeting">Confirm patient payments so appointments become fully confirmed.</div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'R', 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-page-title">Payment Desk</div>
        <div class="ab-page-sub">Review submitted payments and confirm or reject them.</div>

        <?php if ($message): ?><div class="ab-alert ab-alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="ab-alert ab-alert-warning"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="filter-row">
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
                <a href="?<?= http_build_query($query) ?>" class="filter-pill <?= $active ?>">
                    <span><?= $label ?></span>
                    <span class="count"><?= $counts[$key] ?? 0 ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" class="search-form">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
            <input type="text" name="search" class="ab-input" placeholder="Search patient, doctor, or payment reference..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="ab-btn ab-btn-primary"><i class="fas fa-search"></i> Search</button>
            <?php if ($search !== "" || $status_filter !== "all"): ?>
                <a href="payments.php" class="ab-btn ab-btn-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <div class="ab-card">
            <?php if ($rows && $rows->num_rows > 0): ?>
                <?php while ($row = $rows->fetch_assoc()): ?>
                    <?php
                    $derived_status = "unpaid";
                    if ($row["payment_status"] === "paid") {
                        $derived_status = "paid";
                    } elseif ($row["payment_status"] === "pending" && !empty($row["payment_reference"])) {
                        $derived_status = "pending";
                    }
                    ?>
                    <div class="ab-list-row">
                        <div class="ab-icon-chip"><i class="fas fa-receipt"></i></div>
                        <div class="alr-body">
                            <div class="alr-title-line">
                                <span class="alr-title"><?= htmlspecialchars($row["patient_name"]) ?></span>
                                <span class="ab-pill ab-pill-neutral">Dr. <?= htmlspecialchars($row["doctor_name"]) ?></span>
                                <?php if ($derived_status === "paid"): ?>
                                    <span class="ab-pill ab-pill-green"><i class="fas fa-check"></i> Paid</span>
                                <?php elseif ($derived_status === "pending"): ?>
                                    <span class="ab-pill ab-pill-amber"><i class="fas fa-hourglass-half"></i> Pending Confirmation</span>
                                <?php else: ?>
                                    <span class="ab-pill ab-pill-rose"><i class="fas fa-triangle-exclamation"></i> Unpaid</span>
                                <?php endif; ?>
                            </div>
                            <div class="alr-meta">
                                <?= date("D, M j, Y", strtotime($row["appointment_date"])) ?> · <?= date("g:i A", strtotime($row["appointment_time"])) ?>
                                · KSh <?= number_format($row["payment_amount"], 2) ?>
                                · Ref: <?= htmlspecialchars($row["payment_reference"] ?: "N/A") ?>
                                <?= $row["payment_method"] ? " · " . htmlspecialchars($row["payment_method"]) : "" ?>
                                <?= $derived_status === "paid" && $row["payment_date"] ? " · confirmed " . date("M j, Y", strtotime($row["payment_date"])) : "" ?>
                            </div>
                        </div>
                        <div class="alr-trailing">
                            <?php if ($derived_status === "pending"): ?>
                                <form method="POST" style="display:flex;gap:6px">
                                    <input type="hidden" name="appointment_id" value="<?= (int)$row["appointment_id"] ?>">
                                    <button name="action" value="confirm" class="ab-btn ab-btn-primary ab-btn-sm"><i class="fas fa-check"></i> Confirm</button>
                                    <button name="action" value="reject" class="ab-btn ab-btn-danger ab-btn-sm" onclick="return confirm('Reject this payment submission?');"><i class="fas fa-xmark"></i> Reject</button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:.78rem">No action</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.85rem;margin:6px 0 0">No appointment payments matched this view.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
