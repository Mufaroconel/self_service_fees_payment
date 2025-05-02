<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Suppress deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED);

session_start();
// echo session_save_path();
if ($_SESSION['role'] != 'student') {
    header("Location: index.html");
    exit();
}
include('config.php');
require 'db.php';
require 'payment_initiated_helper.php';

// Include our fix for the deprecated utf8_encode function
require_once 'fix_utf8_encode.php';
require_once 'vendor/autoload.php'; // Paynow SDK

use Paynow\Payments\Paynow;

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch student information
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch fees information
$stmt = $conn->prepare("SELECT * FROM fees WHERE user_id = ?");
$stmt->execute([$user_id]);
$fees = $stmt->fetch(PDO::FETCH_ASSOC);

// Replace with your actual Paynow credentials
$paynow = new Paynow(
    PAYNOW_ID,
    PAYNOW_KEY,
    $return_url,
    $return_url
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $payment_reference = $_POST['payment_reference'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $_SESSION['payment_amount'] = $amount;
    // Validate amount
    if ($amount <= 0) {
        $error = "❌ Please enter a valid amount.";
    } else {
        try {
            // Create a new payment
            $payment = $paynow->createPayment("Tuition Payment", $student['fullname']);
            $payment->add("Fees", $amount);
            
            // Add additional information
            $payment->add("Payment Method", $payment_method);
            $payment->add("Reference", $payment_reference);
            $payment->add("Email", $email);
            $payment->add("Phone", $phone);

            $response = $paynow->send($payment);
            echo '<pre>';
            print_r($response);
            echo '</pre>';

            if ($response->success()) {
                // Save reference for verification later
                $_SESSION['poll_url'] = $response->pollUrl();
                $_SESSION['paynow_guid'] = str_replace('guid=', '', parse_url($response->pollUrl(), PHP_URL_QUERY));
                $initiate_payment = insertPaymentInitiation($user_id, $amount);
                header("Location: " . $response->redirectUrl());
                exit;
            } else {
                $error = "❌ Failed to initiate payment. Please try again.";
            }
        } catch (Exception $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - Student Fees Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #1a1f36;
            color: #fff;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .student-info {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .student-info p {
            color: #a0aec0;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .student-info .student-name {
            color: #fff;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .student-info .student-id {
            color: #a0aec0;
            font-size: 0.875rem;
        }

        .nav-menu {
            padding: 1.5rem 0;
        }

        .nav-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            color: #a0aec0;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .nav-item.active {
            background-color: #3182ce;
            color: #fff;
        }

        .nav-item i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .welcome-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .balance-info {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: #ebf8ff;
            border-radius: 0.375rem;
            border-left: 4px solid #3182ce;
        }

        .balance-info h3 {
            color: #2c5282;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .balance-info p {
            color: #4a5568;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .currency-prefix {
            position: relative;
        }

        .currency-prefix input {
            padding-left: 3rem;
        }

        .currency-prefix::before {
            content: 'ZWL';
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            font-weight: 500;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-method {
            position: relative;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #3182ce;
            background-color: #f7fafc;
        }

        .payment-method.selected {
            border-color: #3182ce;
            background-color: #ebf8ff;
        }

        .payment-method input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .payment-method .method-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .payment-method .method-name {
            font-weight: 500;
            color: #2d3748;
        }

        .submit-button {
            background-color: #3182ce;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-button:hover {
            background-color: #2c5282;
        }

        .message {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .error {
            background-color: #fed7d7;
            color: #c53030;
        }

        .success {
            background-color: #c6f6d5;
            color: #2f855a;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: #4a5568;
            text-decoration: none;
            margin-top: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #2d3748;
        }

        .back-link i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Student Portal</h2>
            </div>
            <div class="student-info">
                <p class="student-name"><?php echo htmlspecialchars($student['fullname'] ?? 'Student'); ?></p>
                <p class="student-id">Level: <?php echo htmlspecialchars($student['level'] ?? 'N/A'); ?></p>
            </div>
            <nav class="nav-menu">
                <a href="student_dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="make_payment.php" class="nav-item active">
                    <i class="fas fa-money-bill-wave"></i>
                    Make Payment
                </a>
                <a href="payment_history.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    Payment History
                </a>
                <a href="check_balance.php" class="nav-item">
                    <i class="fas fa-wallet"></i>
                    Check Balance
                </a>
                <a href="student_notification.php" class="nav-item">
                    <i class="fas fa-bell"></i> 
                    Notifications
                </a>

                <a href="change_password.php" class="nav-item">
                    <i class="fas fa-key"></i>
                    Change Password
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1 class="welcome-text">Make a Payment</h1>
            </div>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="message success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="balance-info">
                    <h3>Current Balance</h3>
                    <p>Your outstanding balance is: <strong>ZWL <?php echo number_format($fees['balance'] ?? 0, 2); ?></strong></p>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="amount">Amount to Pay</label>
                        <div class="currency-prefix">
                            <input type="number" id="amount" name="amount" step="0.01" placeholder="Enter amount" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="ecocash" required>
                                <div class="method-icon"><i class="fas fa-mobile-alt"></i></div>
                                <div class="method-name">EcoCash</div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="onemoney">
                                <div class="method-icon"><i class="fas fa-mobile-alt"></i></div>
                                <div class="method-name">OneMoney</div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="telecash">
                                <div class="method-icon"><i class="fas fa-mobile-alt"></i></div>
                                <div class="method-name">TeleCash</div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="bank">
                                <div class="method-icon"><i class="fas fa-university"></i></div>
                                <div class="method-name">Bank Transfer</div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_reference">Payment Reference</label>
                        <input type="text" id="payment_reference" name="payment_reference" placeholder="Enter payment reference" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
                    </div>

                    <button type="submit" class="submit-button">
                        <i class="fas fa-credit-card"></i>
                        Pay with PayNow
                    </button>
    </form>

                <a href="student_dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </main>
    </div>

    <script>
        // Add selected class to payment method when clicked
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    </script>
</body>
</html>
