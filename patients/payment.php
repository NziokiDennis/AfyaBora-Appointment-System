<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$appointment_id = $_GET["appointment_id"] ?? null;
$success = "";
$error = "";
$appointment = null;
$processing = false;

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
                         a.payment_status, a.payment_amount, u.full_name AS doctor_name
                  FROM appointments a
                  JOIN users u ON a.doctor_id = u.user_id
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
        
        // Update database - Mark as paid
        $update_query = "UPDATE appointments 
                         SET payment_status = 'paid', 
                             payment_date = NOW(), 
                             payment_method = ?,
                             payment_reference = ?
                         WHERE appointment_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $payment_method, $payment_reference, $appointment_id);
        
        if ($stmt->execute()) {
            if ($payment_method == "M-Pesa") {
                $success = "mpesa"; // Special flag for M-Pesa success
                $_SESSION['mpesa_details'] = [
                    'transaction_id' => $payment_reference,
                    'phone' => $phone_number,
                    'amount' => $appointment["payment_amount"],
                    'date' => date('d/m/Y H:i')
                ];
            } else {
                $success = "Payment successful! Reference: " . $payment_reference;
            }
            
            // Refresh appointment data
            $stmt = $conn->prepare("SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason, 
                                           a.payment_status, a.payment_amount, u.full_name AS doctor_name
                                    FROM appointments a
                                    JOIN users u ON a.doctor_id = u.user_id
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
if ($mpesa_details && $success == "mpesa") {
    unset($_SESSION['mpesa_details']); // Clear after use
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Appointment Fee</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .payment-card {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        }
        .appointment-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .payment-amount {
            font-size: 2em;
            color: #28a745;
            font-weight: bold;
        }
        .payment-method-option {
            border: 2px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method-option:hover {
            border-color: #007bff;
            background: #f0f8ff;
        }
        .payment-method-option.selected {
            border-color: #28a745;
            background: #d4edda;
        }
        .payment-method-option input[type="radio"] {
            margin-right: 10px;
        }
        .payment-details {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .paid-badge {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
        }
        
        /* M-Pesa Specific Styles */
        .mpesa-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #00a651 0%, #008f47 100%);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
        }
        .mpesa-success {
            background: linear-gradient(135deg, #00a651 0%, #008f47 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        .mpesa-success h3 {
            color: white;
        }
        .receipt-box {
            background: white;
            color: #333;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .receipt-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        /* Processing Animation */
        .processing-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .processing-overlay.active {
            display: flex;
        }
        .processing-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
        }
        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #00a651;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .phone-icon {
            font-size: 50px;
            color: #00a651;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
    </style>
</head>
<body>

    <?php include "navbar.php"; ?>

    <!-- Processing Overlay for M-Pesa STK Push Simulation -->
    <div class="processing-overlay" id="processingOverlay">
        <div class="processing-content">
            <div class="phone-icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h4>Processing M-Pesa Payment...</h4>
            <div class="spinner"></div>
            <p>Please check your phone for the M-Pesa prompt</p>
            <p class="text-muted"><small>Enter your M-Pesa PIN to complete payment</small></p>
        </div>
    </div>

    <div class="container">
        <div class="payment-card">
            <h2 class="text-center"><i class="fas fa-credit-card"></i> Appointment Payment</h2>

            <?php if ($success == "mpesa" && $mpesa_details): ?>
                <!-- M-Pesa Success Receipt -->
                <div class="mpesa-success">
                    <div style="font-size: 60px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Payment Successful!</h3>
                    <p>You have received this confirmation from M-Pesa</p>
                    
                    <div class="receipt-box">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div class="mpesa-logo" style="margin: 0 auto;">M</div>
                            <h5 style="margin-top: 10px;">M-Pesa Receipt</h5>
                        </div>
                        <div class="receipt-row">
                            <span>Transaction ID:</span>
                            <strong><?= $mpesa_details['transaction_id'] ?></strong>
                        </div>
                        <div class="receipt-row">
                            <span>Phone Number:</span>
                            <strong><?= $mpesa_details['phone'] ?></strong>
                        </div>
                        <div class="receipt-row">
                            <span>Paid To:</span>
                            <strong>Bilpham Hospital</strong>
                        </div>
                        <div class="receipt-row">
                            <span>Date & Time:</span>
                            <strong><?= $mpesa_details['date'] ?></strong>
                        </div>
                        <div class="receipt-row">
                            <span>Amount Paid:</span>
                            <strong class="text-success">KSh <?= number_format($mpesa_details['amount'], 2) ?></strong>
                        </div>
                    </div>
                    
                    <p class="mt-3"><small>An SMS confirmation has been sent to your phone</small></p>
                </div>
                <div class="text-center mt-3">
                    <a href="dashboard.php" class="btn btn-light btn-lg">
                        <i class="fas fa-home"></i> Go to Dashboard
                    </a>
                </div>
                
            <?php elseif ($success && $success != "mpesa"): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <div class="text-center">
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
                
            <?php elseif ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($appointment && $appointment["payment_status"] != "paid"): ?>
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
                            <input type="tel" name="phone_number" id="mpesa_phone" class="form-control" 
                                   placeholder="712345678" pattern="254[0-9]{9}" 
                                   title="Enter phone number starting with 254">
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>How it works:</strong>
                            <ol class="mb-0 mt-2">
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
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> Card payment is simulated for demo purposes
                        </div>
                    </div>

                    <div class="payment-method-option" onclick="selectPayment('bank')" id="bank-option">
                        <label>
                            <input type="radio" name="payment_method" value="Bank Transfer" id="bank" required>
                            <i class="fas fa-university"></i> <strong>Bank Transfer</strong>
                        </label>
                    </div>
                    <div id="bank-details" class="payment-details">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> Bank transfer is simulated for demo purposes
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success btn-lg w-100" id="payBtn">
                            <i class="fas fa-lock"></i> Pay KSh <?php echo number_format($appointment["payment_amount"], 2); ?>
                        </button>
                    </div>
                    <p class="text-center text-muted mt-3">
                        <small><i class="fas fa-shield-alt"></i> Secure mock payment for demonstration</small>
                    </p>
                </form>
                
            <?php elseif ($appointment && $appointment["payment_status"] == "paid"): ?>
                <div class="text-center">
                    <div class="paid-badge">
                        <i class="fas fa-check-circle"></i> PAYMENT COMPLETED
                    </div>
                    <p class="text-muted">Your appointment has been confirmed!</p>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Appointment not found or you don't have permission to view this page.
                </div>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>

    <?php include "../partials/footer.php"; ?>

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
