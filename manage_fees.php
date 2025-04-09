<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$stmt = $conn->prepare("SELECT users.id, users.username, fees.total_fee, fees.balance
                        FROM users
                        LEFT JOIN fees ON users.id = fees.user_id
                        WHERE users.role = 'student'");
$stmt->execute();
$students = $stmt->fetchAll();
?>

<h2>Manage Student Fees</h2>
<table border="1" cellpadding="10">
    <tr>
        <th>Username</th>
        <th>Total Fee</th>
        <th>Balance</th>
        <th>Action</th>
    </tr>
    <?php foreach ($students as $student): ?>
    <tr>
        <td><?= $student['username'] ?></td>
        <td>$<?= $student['total_fee'] ?? '0.00' ?></td>
        <td>$<?= $student['balance'] ?? '0.00' ?></td>
        <td><a href="edit_fee.php?id=<?= $student['id'] ?>">Edit Fee</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<br><a href="admin_dashboard.php">⬅ Back to Dashboard</a>
