<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY paid_at DESC");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll();

echo "<h2>Payment History</h2>";
if ($payments) {
    foreach ($payments as $payment) {
        echo "Paid $" . $payment['amount'] . " on " . $payment['paid_at'] . "<br>";
    }
} else {
    echo "No payments found.";
}
?>
<br><a href="student_dashboard.php">⬅ Back</a>
