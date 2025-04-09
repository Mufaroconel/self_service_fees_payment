<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}
?>

<h2>Welcome Admin</h2>
<ul>
    <li><a href="create_student.php">Create Student</a></li>
    <li><a href="manage_fees.php">Manage Student Fees</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>
