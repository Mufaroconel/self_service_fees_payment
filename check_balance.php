<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT total_fee, balance FROM fees WHERE user_id = ?");
$stmt->execute([$user_id]);
$data = $stmt->fetch();

echo "<h2>Fee Balance</h2>";
if ($data) {
    echo "Total Fee: $" . $data['total_fee'] . "<br>";
    echo "Remaining Balance: $" . $data['balance'];
} else {
    echo "No fee record found.";
}
?>
<br><a href="student_dashboard.php">⬅ Back</a>
