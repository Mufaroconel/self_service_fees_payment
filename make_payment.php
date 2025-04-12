<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';
require_once 'vendor/autoload.php'; // Paynow SDK

use Paynow\Payments\Paynow;

$user_id = $_SESSION['user_id'];

// Replace with your actual Paynow credentials
$paynow = new Paynow(
    'YOUR_INTEGRATION_ID',
    'YOUR_INTEGRATION_KEY',
    'http://localhost/self_service_system/return.php', // return_url after payment
    'http://localhost/self_service_system/result.php'  // result_url callback from Paynow
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];

    // Fetch student name or email for Paynow display
    $stmt = $conn->prepare("SELECT fullname FROM students WHERE id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
    $studentName = $student['fullname'] ?? 'Student';

    // Create a new payment
    $payment = $paynow->createPayment("Tuition Payment", $studentName);
    $payment->add("Fees", $amount);

    $response = $paynow->send($payment);

    if ($response->success()) {
        // Save reference for verification later
        $_SESSION['paynow_reference'] = $response->pollUrl;
        header("Location: " . $response->redirectUrl());
        exit;
    } else {
        $error = "❌ Failed to initiate payment. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make a Payment</title>
    <style>
        /* same CSS as before */
    </style>
</head>
<body>
    <form class="payment-form" method="POST">
        <h2>Make a Payment</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <label for="amount">Amount to Pay ($):</label>
        <input type="number" step="0.01" name="amount" id="amount" placeholder="Enter amount" required>
        <button type="submit">Pay with Paynow</button>
    </form>

    <a href="student_dashboard.php" class="back-link">⬅ Back to Dashboard</a>
</body>
</html>
