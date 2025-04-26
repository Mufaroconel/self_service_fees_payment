<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['PHPSESSID'])) {
    session_id($_GET['PHPSESSID']);  // restore the original session
}
session_start();

// Log to verify
file_put_contents("/tmp/after.txt", "Session ID: " . session_id() . "\n" . print_r($_SESSION, true));

// Redirect if session is invalid
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.html");
    exit();
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Payment Returned</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .message-box {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .message-box h2 {
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class='message-box'>
        <h2>🎉 You have returned from Paynow</h2>
        <p>Please wait while we verify your payment.</p>
        <p><a href='student_dashboard.php'>Go back to dashboard</a></p>
        <hr>
        <h3>🔐 Session Info (Debug)</h3>
        <pre>" . print_r($_SESSION, true) . "</pre>
    </div>
</body>
</html>";
?>
