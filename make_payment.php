<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];

    // Update balance
    $stmt = $conn->prepare("UPDATE fees SET balance = balance - ? WHERE user_id = ?");
    $stmt->execute([$amount, $user_id]);

    // Record payment
    $stmt = $conn->prepare("INSERT INTO payments (user_id, amount) VALUES (?, ?)");
    $stmt->execute([$user_id, $amount]);

    echo "✅ Payment recorded!";
}
?>

<h2>Make a Payment</h2>
<form method="POST">
    <label>Amount to Pay ($):</label>
    <input type="number" step="0.01" name="amount" required>
    <br><br>
    <button type="submit">Submit Payment</button>
</form>
<br><a href="student_dashboard.php">⬅ Back</a>
