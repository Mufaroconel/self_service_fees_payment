<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$user_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $total = $_POST['total_fee'];
    $balance = $_POST['balance'];

    // Check if record exists
    $stmt = $conn->prepare("SELECT * FROM fees WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->rowCount() > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE fees SET total_fee = ?, balance = ? WHERE user_id = ?");
        $stmt->execute([$total, $balance, $user_id]);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO fees (user_id, total_fee, balance) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $total, $balance]);
    }

    echo "✅ Fee updated!";
}

$stmt = $conn->prepare("SELECT * FROM fees WHERE user_id = ?");
$stmt->execute([$user_id]);
$fee = $stmt->fetch();
?>

<h2>Edit Fees</h2>
<form method="POST">
    <label>Total Fee:</label>
    <input type="number" name="total_fee" step="0.01" value="<?= $fee['total_fee'] ?? 0 ?>" required><br><br>

    <label>Balance:</label>
    <input type="number" name="balance" step="0.01" value="<?= $fee['balance'] ?? 0 ?>" required><br><br>

    <button type="submit">Update</button>
</form>
<br><a href="manage_fees.php">⬅ Back</a>