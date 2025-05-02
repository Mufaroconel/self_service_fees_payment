<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['PHPSESSID'])) {
    session_id($_GET['PHPSESSID']);  // restore the original session
}
session_start();

// Redirect if session is invalid
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.html");
    exit();
}
include('config.php');
// Load Paynow library
require_once 'vendor/autoload.php'; // adjust if needed

// 👉 Load your new confirm_delivery.php function
require_once 'confirm_delivery.php';
require_once 'insert_payment_helper.php';
require_once 'update_fees_helper.php';


use Paynow\Payments\Paynow;


// Your Paynow credentials
$paynow = new Paynow(
    PAYNOW_ID,
    PAYNOW_KEY,
    $return_url,
    $return_url
);

// Check if poll_url exists
$status_message = "🔍 Unable to retrieve payment status.";
if (isset($_SESSION['poll_url'])) {
    $pollUrl = $_SESSION['poll_url'];
    $status = $paynow->pollTransaction($pollUrl);
    $status_message = "💳 Payment Status: <strong>" . $status->status() . "</strong>";

    if (strtolower(trim($status->status())) === 'awaiting delivery') {
        if (isset($_SESSION['paynow_guid'])) {
            $guid = $_SESSION['paynow_guid'];
            $confirm_result = confirmDelivery($guid);
    
            if ($confirm_result['success']) {
                $status_message .= "<br>✅ Delivery confirmed successfully!";
                
                // 🆕 INSERT into payments
                if (isset($_SESSION['user_id']) && isset($_SESSION['payment_amount']) && isset($_SESSION['paynow_guid']) && isset($_SESSION['poll_url'])) {
                    $user_id = $_SESSION['user_id'];
                    $amount = $_SESSION['payment_amount'];
                    $paynow_guid = $_SESSION['paynow_guid'];
                    $poll_url = $_SESSION['poll_url'];
                    $paid_at = date('Y-m-d H:i:s');

                    $insert_result = insertPayment($user_id, $amount, $paynow_guid, $poll_url, $paid_at);

                    if ($insert_result['success']) {
                        $status_message .= "<br>📝 New payment record created!";
                        // Update fee balance
                        $feeResult = getTotalFee($user_id);
                        if ($feeResult['success']) {
                            $current_balance = $feeResult['data']['balance'];
                            $new_balance = calculateNewBalance($current_balance, $amount);

                            $updateResult = updateBalance($user_id, $new_balance);
                            if ($updateResult['success']) {
                                $status_message .= "<br>💸 Fee balance updated successfully!";
                                $message = "Your payment of $$amount was received successfully. Your remaining balance is $$new_balance.";
                                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                                $stmt->execute([$user_id, 'Payment Successful', $message]);


                            } else {
                                $status_message .= "<br>⚠️ Failed to update balance: " . $updateResult['message'];
                            }
                        }
                    } else {
                        $status_message .= "<br>⚠️ Failed to insert payment: " . $insert_result['message'];
                    }
                } else {
                    $status_message .= "<br>⚠️ Missing session data for inserting payment.";
                }
                // 🆕 After confirming delivery, poll again to get new status
                $status = $paynow->pollTransaction($pollUrl);
    
                // Update the message based on new status
                if ($status->paid()) {
                    $status_message .= "<br>🎉 Payment Status Updated: <strong>Paid</strong>!";
                } elseif (strtolower($status->status()) === 'delivered') {
                    $status_message .= "<br>🎉 Payment Status Updated: <strong>Delivered</strong>!";
                } else {
                    $status_message .= "<br>ℹ️ Payment Status Still: <strong>" . strtolower($status->status()) . "</strong>";
                }
                
            } else {
                $status_message .= "<br>❗ Delivery confirmation failed: " . $confirm_result['message'];
            }
        } else {
            $status_message .= "<br>⚠️ No GUID found for confirming delivery.";
        }
    }
    
    
}
echo "<meta http-equiv='refresh' content='3;url=student_dashboard.php'>";
?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Payment Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .message-box {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .message-box h2 {
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class='message-box'>
        <div class='success-icon'>✅</div>
        <h2>Payment Successful!</h2>
        <p>Thank you. Your payment has been received.</p>
        <p>Redirecting you to your dashboard...</p>
    </div>
</body>
</html>
