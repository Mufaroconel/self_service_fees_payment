<?php
session_start();
require 'db.php';

// Check if the user is an admin
if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

if (isset($_POST['application_id']) && isset($_POST['status'])) {
    $application_id = $_POST['application_id'];
    $status = $_POST['status'];

    // Update application status
    $stmt = $conn->prepare("UPDATE applications SET application_status = ? WHERE id = ?");
    $stmt->execute([$status, $application_id]);

    echo "Application updated successfully.";
    header("Location: admin_view.php");
    exit();
}
?>
