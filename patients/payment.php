<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$current_page = "book_appointment";
$appointment_id = $_GET["appointment_id"] ?? null;
$success = "";
$error = "";
$appointment = null;
$processing = false;

function derivePaymentStage($appointment) {
    if (($appointment["payment_status"] ?? "") === "paid") {
        return "paid";
    }

    $hasSubmission = !empty($appointment["payment_reference"]) || !empty($appointment["payment_method"]);
    return $hasSubmission ? "pending" : "unpaid";
}

// Fetch appointment details
if ($appointment_id) {
    // Get patient ID
    $patient_stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $patient_stmt->bind_param("i", $user_id);
    $patient_stmt->execute();
    $patient_result = $patient_stmt->get_result();
    $patient_data = $patient_result->fetch_assoc();
    
    if ($patient_data) {
        $patient_id = $patient_data["patient_id"];
        
        // Fetch appointment details
        $query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason,
                         a.payment_status, a.payment_amount, a.payment_method, a.payment_reference,
                         u.full_name AS doctor_name
                  FROM appointments a
                  JOIN users u ON a.doctor_id = u.user_id AND u.role = 'doctor'
                  WHERE a.appointment_id = ? AND a.patient_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $appointment_id, $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
    }
}

// Handle payment submission - MOCK M-PESA PAYMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && $appointment) {
    $payment_method = $_POST["payment_method"];
    $phone_number = $_POST["phone_number"] ?? '';
    
    // Validate phone number for M-Pesa
    if ($payment_method == "M-Pesa") {
        if (empty($phone_number) || !preg_match('/^254[0-9]{9}$/', $phone_number)) {
            $error = "Please enter a valid M-Pesa phone number (format: 254712345678)";
        }
    }
    
    if (!$error) {
        // MOCK PAYMENT SIMULATION - No real money is processed
        // This simulates how M-Pesa STK Push would work in production
        
        $processing = true; // Show processing screen
        
        // Generate mock M-Pesa transaction code (similar to real M-Pesa format)
        if ($payment_method == "M-Pesa") {
            // M-Pesa transaction codes look like: QGH8LMXYZ1
            $payment_reference = strtoupper(substr(md5(time() . $phone_number), 0, 10));
        } else {
            $payment_reference = "PAY-" . strtoupper(uniqid());
        }
        
        // Store patient-submitted payment and wait for receptionist confirmation
        $update_query = "UPDATE appointments 
                         SET payment_status = 'pending',
                             payment_date = NULL,
                             payment_method = ?,
                             payment_reference = ?
                         WHERE appointment_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $payment_method, $payment_reference, $appointment_id);
        
        if ($stmt->execute()) {
            if ($payment_method == "M-Pesa") {
                $success = "mpesa_pending"; // Special flag for M-Pesa submission
                $_SESSION['mpesa_details'] = [
                    'transaction_id' => $payment_reference,
                    'phone' => $phone_number,
                    'amount' => $appointment["payment_amount"],
                    'date' => date('d/m/Y H:i')
                ];
            } else {
                $success = "Payment submitted successfully. Reference: " . $payment_reference . ". A receptionist will confirm it.";
            }
            
            // Refresh appointment data
            $stmt = $conn->prepare("SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason,
                                           a.payment_status, a.payment_amount, a.payment_method, a.payment_reference,
                                           u.full_name AS doctor_name
                                    FROM appointments a
                                    JOIN users u ON a.doctor_id = u.user_id AND u.role = 'doctor'
                                    WHERE a.appointment_id = ? AND a.patient_id = ?");
            $stmt->bind_param("ii", $appointment_id, $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $appointment = $result->fetch_assoc();
        } else {
            $error = "Error processing payment. Please try again.";
        }
    }
}

// Get M-Pesa details from session if available
$mpesa_details = $_SESSION['mpesa_details'] ?? null;
if ($mpesa_details && $success == "mpesa_pending") {
    unset($_SESSION['mpesa_details']); // Clear after use
}

$payment_stage = $appointment ? derivePaymentStage($appointment) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .appointment-summary { background: var(--sky); padding: 16px; border-radius: var(--radius-md); margin-bottom: 20px; }
        .appointment-summary p { margin: 0 0 6px; font-size: .88rem; }
        .appointment-summary hr { border-color: var(--border); margin: 12px 0; }
        .payment-amount { font-size: 1.9rem; color: var(--navy); font-weight: 800; }

        .payment-method-option {
            border: 1.5px solid var(--border); padding: 14px 16px; border-radius: var(--radius-md);
            margin-bottom: 10px; cursor: pointer; transition: all .15s;
        }
        .payment-method-option:hover { border-color: var(--blue); background: var(--sky); }
        .payment-method-option.selected { border-color: var(--green); background: rgba(31,174,122,.06); }
        .payment-method-option input[type="radio"] { margin-right: 10px; }
        .payment-details { display: none; margin-top: 14px; padding: 14px; background: var(--sky); border-radius: var(--radius-md); }

        .paid-badge {
            background: rgba(31,174,122,.12); color: var(--green); font-weight: 700;
            padding: 10px 20px; border-radius: var(--radius-pill); display: inline-block; margin: 16px 0;
        }
        .pending-badge {
            background: rgba(245,158,11,.14); color: #b45309; font-weight: 700;
            padding: 10px 20px; border-radius: var(--radius-pill); display: inline-block; margin: 16px 0;
        }

        .mpesa-logo {
            width: 52px; height: 52px; background: linear-gradient(135deg, #00a651 0%, #008f47 100%);
            border-radius: var(--radius-md); display: inline-flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 18px; margin-right: 14px; vertical-align: middle;
        }
        .mpesa-success { background: linear-gradient(135deg, #00a651 0%, #008f47 100%); color: white; padding: 28px; border-radius: var(--radius-lg); text-align: center; }
        .mpesa-success h3 { color: white; margin: 10px 0; }
        .receipt-box { background: white; color: var(--navy); padding: 18px; border-radius: var(--radius-md); margin-top: 18px; text-align: left; }
        .receipt-row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--border); font-size: .88rem; }
        .receipt-row:last-child { border-bottom: none; font-weight: 700; font-size: 1rem; }

        .processing-overlay { display: none; position: fixed; inset: 0; background: rgba(0,45,112,.75); z-index: 9999; justify-content: center; align-items: center; }
        .processing-overlay.active { display: flex; }
        .processing-content { background: white; padding: 36px; border-radius: var(--radius-lg); text-align: center; max-width: 380px; }
        .spinner { width: 54px; height: 54px; border: 5px solid #f3f3f3; border-top: 5px solid #00a651; border-radius: 50%; animation: spin 1s linear infinite; margin: 18px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .phone-icon { font-size: 46px; color: #00a651; animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: .8; } }

        .ab-info-inline { background: var(--sky); border-radius: var(--radius-sm); padding: 10px 12px; font-size: .8rem; color: var(--navy); margin-top: 8px; }
        .ab-info-inline ol { margin: 6px 0 0 18px; padding: 0; }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<!-- Processing Overlay for M-Pesa STK Push Simulation -->
<div class="processing-overlay" id="processingOverlay">
    <div class="processing-content">
        <div class="phone-icon"><i class="fas fa-mobile-alt"></i></div>
        <h4>Processing M-Pesa Payment...</h4>
        <div class="spinner"></div>
        <p>Please check your phone for the M-Pesa prompt</p>
        <p style="color:var(--muted);font-size:.8rem">Enter your M-Pesa PIN to complete payment</p>
    </div>
</div>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Appointment Payment</div>
            <div class="ab-subgreeting">Confirm your appointment by completing payment.</div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION["full_name"] ?? "P", 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION["full_name"] ?? "") ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-center-viewport">
        <div class="ab-page-title">Payment</div>
        <div class="ab-page-sub">A mock payment flow for demonstration — no real money is processed.</div>

        <div class="ab-card">

            <?php if ($success == "mpesa_pending" && $mpesa_details): ?>
                <!-- M-Pesa Pending Confirmation -->
                <div class="mpesa-success">
                    <div style="font-size: 60px; margin-bottom: 20px;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h3>Payment Submitted</h3>
                    <p>Your payment details have been captured and are awaiting receptionist confirmation.</p>
                    
                    <div class="receipt-box">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div class="mpesa-logo" style="margin: 0 auto;">M</div>
                            <h5 style="margin-top: 10px;">M-Pesa Submission Details</h5>
                        </div>
                        <div class="receipt-row">
                            <span>Transaction ID:</span>
                            <strong><?= htmlspecialchars($mpesa_details['transaction_id']) ?></strong>
                        </div>
                        <div class="receipt-row">
                            <span>Phone Number:</span>
                            <strong><?= htmlspecialchars($mpesa_details['phone']) ?></strong>
                        </div>
                        <div class="receipt-row">
                            <span>Paid To:</span>
                            <strong>Bilpham Hospital</strong>
                        </div>
                        <div class="receipt-row">
                            <span>Submitted On:</span>
                            <strong><?= htmlspecialchars($mpesa_details['date']) ?></strong>
                        </div>
                        <div class="receipt-row">
                            <span>Amount Paid:</span>
                            <strong class="text-success">KSh <?= number_format($mpesa_details['amount'], 2) ?></strong>
                        </div>
                    </div>
                    
                    <p class="mt-3"><small>You can download the official receipt after the receptionist confirms the payment.</small></p>
                </div>
                <div class="text-center mt-3">
                    <a href="dashboard.php" class="ab-btn ab-btn-secondary"><i class="fas fa-house"></i> Go to Dashboard</a>
                </div>
                
            <?php elseif ($success && $success != "mpesa"): ?>
                <div class="ab-alert ab-alert-success"><i class="fas fa-circle-check"></i> <?php echo $success; ?></div>
                <div class="text-center">
                    <a href="dashboard.php" class="ab-btn ab-btn-primary">Go to Dashboard</a>
                </div>
                
            <?php elseif ($error): ?>
                <div class="ab-alert ab-alert-danger"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($appointment && $payment_stage == "unpaid"): ?>
                <div class="appointment-summary">
                    <h5><i class="fas fa-calendar-check"></i> Appointment Details</h5>
                    <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($appointment["doctor_name"]); ?></p>
                    <p><strong>Date:</strong> <?php echo date("l, F j, Y", strtotime($appointment["appointment_date"])); ?></p>
                    <p><strong>Time:</strong> <?php echo date("g:i A", strtotime($appointment["appointment_time"])); ?></p>
                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($appointment["reason"]); ?></p>
                    <hr>
                    <p class="text-center mb-0">
                        <strong>Amount to Pay:</strong><br>
                        <span class="payment-amount">KSh <?php echo number_format($appointment["payment_amount"], 2); ?></span>
                    </p>
                </div>

                <form method="POST" id="paymentForm">
                    <h5 class="mb-3">Select Payment Method</h5>
                    
                    <!-- M-Pesa Option (Featured) -->
                    <div class="payment-method-option" onclick="selectPayment('mpesa')" id="mpesa-option">
                        <label class="w-100">
                            <input type="radio" name="payment_method" value="M-Pesa" id="mpesa" required>
                            <span class="mpesa-logo">M</span>
                            <strong>M-Pesa (Recommended)</strong>
                            <p class="mb-0 text-muted" style="margin-left: 85px;">
                                <small>Pay securely with your mobile phone</small>
                            </p>
                        </label>
                    </div>
                    <div id="mpesa-details" class="payment-details">
                        <label>M-Pesa Phone Number</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text">🇰🇪 +254</span>
                            <input type="tel" name="phone_number" id="mpesa_phone" class="ab-input"
                                   placeholder="712345678" pattern="254[0-9]{9}"
                                   title="Enter phone number starting with 254">
                        </div>
                        <div class="ab-info-inline">
                            <i class="fas fa-info-circle"></i> <strong>How it works:</strong>
                            <ol>
                                <li>Enter your M-Pesa registered phone number</li>
                                <li>You'll receive a payment prompt on your phone</li>
                                <li>Enter your M-Pesa PIN to complete payment</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Other Payment Options -->
                    <div class="payment-method-option" onclick="selectPayment('card')" id="card-option">
                        <label>
                            <input type="radio" name="payment_method" value="Credit/Debit Card" id="card" required>
                            <i class="fas fa-credit-card"></i> <strong>Credit/Debit Card</strong>
                        </label>
                    </div>
                    <div id="card-details" class="payment-details">
                        <div class="ab-info-inline"><i class="fas fa-info-circle"></i> Card payment is simulated for demo purposes</div>
                    </div>

                    <div class="payment-method-option" onclick="selectPayment('bank')" id="bank-option">
                        <label>
                            <input type="radio" name="payment_method" value="Bank Transfer" id="bank" required>
                            <i class="fas fa-university"></i> <strong>Bank Transfer</strong>
                        </label>
                    </div>
                    <div id="bank-details" class="payment-details">
                        <div class="ab-info-inline"><i class="fas fa-info-circle"></i> Bank transfer is simulated for demo purposes</div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="ab-btn ab-btn-primary" style="width:100%;justify-content:center" id="payBtn">
                            <i class="fas fa-lock"></i> Pay KSh <?php echo number_format($appointment["payment_amount"], 2); ?>
                        </button>
                    </div>
                    <p class="text-center text-muted mt-3">
                        <small><i class="fas fa-shield-alt"></i> Secure mock payment for demonstration</small>
                    </p>
                </form>

            <?php elseif ($appointment && $payment_stage == "pending"): ?>
                <div class="text-center">
                    <div class="pending-badge">
                        <i class="fas fa-hourglass-half"></i> PAYMENT AWAITING CONFIRMATION
                    </div>
                    <p class="text-muted">Your payment has been submitted. The receptionist will confirm it shortly.</p>
                    <?php if (!empty($appointment["payment_reference"])): ?>
                        <p><strong>Reference:</strong> <?= htmlspecialchars($appointment["payment_reference"]) ?></p>
                    <?php endif; ?>
                    <a href="dashboard.php" class="ab-btn ab-btn-primary">Go to Dashboard</a>
                </div>
                
            <?php elseif ($appointment && $payment_stage == "paid"): ?>
                <div class="text-center">
                    <div class="paid-badge">
                        <i class="fas fa-check-circle"></i> PAYMENT COMPLETED
                    </div>
                    <p class="text-muted">Your appointment has been confirmed!</p>
                    <a href="receipt.php?appointment_id=<?= $appointment["appointment_id"] ?>" class="ab-btn ab-btn-secondary" style="margin-bottom:10px"><i class="fas fa-download"></i> Download Receipt</a><br>
                    <a href="dashboard.php" class="ab-btn ab-btn-primary">Go to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="ab-alert ab-alert-danger"><i class="fas fa-triangle-exclamation"></i> Appointment not found or you don't have permission to view this page.</div>
                <a href="dashboard.php" class="ab-btn ab-btn-primary">Go to Dashboard</a>
            <?php endif; ?>
        </div>
        </div>
    </main>
</div>

    <script>
        function selectPayment(method) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-method-option').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to chosen option
            document.getElementById(method + '-option').classList.add('selected');
            
            // Hide all payment details
            document.querySelectorAll('.payment-details').forEach(el => el.style.display = 'none');
            
            // Show selected payment details
            document.getElementById(method + '-details').style.display = 'block';
            
            // Check the radio button
            document.getElementById(method).checked = true;
        }

        // Auto-format M-Pesa phone number
        document.getElementById('mpesa_phone')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            
            // Auto-add 254 if user starts with 0 or 7
            if (value.startsWith('0')) {
                value = '254' + value.substring(1);
            } else if (value.startsWith('7') || value.startsWith('1')) {
                value = '254' + value;
            }
            
            // Limit to 12 digits (254 + 9 digits)
            if (value.length > 12) {
                value = value.substring(0, 12);
            }
            
            e.target.value = value;
        });

        // Show processing overlay for M-Pesa payments
        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (selectedMethod && selectedMethod.value === 'M-Pesa') {
                // Show processing overlay
                document.getElementById('processingOverlay').classList.add('active');
                
                // Disable form submission button
                document.getElementById('payBtn').disabled = true;
                
                // Simulate processing delay (3 seconds to mimic STK push)
                setTimeout(function() {
                    // Form will submit after delay
                }, 3000);
            }
        });

        // Pre-select M-Pesa on page load
        window.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('mpesa')) {
                selectPayment('mpesa');
            }
        });
    </script>

</body>
</html>
