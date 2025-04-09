<?php
session_start();
if ($_SESSION['role'] != 'student') {
    header("Location: index.html");
    exit();
}
?>

<h2>Student Dashboard</h2>
<ul>
    <li><a href="check_balance.php">Check Balance</a></li>
    <li><a href="payment_history.php">View Payment History</a></li>
    <li><a href="make_payment.php">Make Payment</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>
