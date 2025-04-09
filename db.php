<?php
$host = 'localhost';
$db = 'school_banking_system';
$user = 'root';
$pass = ''; // default XAMPP password is empty

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: Set fetch mode to associative
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>