<?php
require 'db.php';
$user_id = 2; // Change this to match a real student user ID
$total = 500;
$stmt = $conn->prepare("INSERT INTO fees (user_id, total_fee, balance) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $total, $total]);
echo "Fee record inserted.";
?>
