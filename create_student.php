<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // hash the password
    $role = "student";

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $password, $role])) {
        echo "✅ Student account created successfully.";
    } else {
        echo "❌ Failed to create student.";
    }
}
?>
<h2>Create Student Account</h2>
<form method="POST" action="">
    <label>Username:</label>
    <input type="text" name="username" required><br><br>

    <label>Password:</label>
    <input type="password" name="password" required><br><br>

    <button type="submit">Create Student</button>
</form>
<a href="admin_dashboard.php">⬅ Back to Dashboard</a>
