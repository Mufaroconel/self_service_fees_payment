<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare SQL to find user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Save user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit();
    } else {
        // Log the error
        $logMessage = date('Y-m-d H:i:s') . " - Failed login attempt for username: " . $_POST['username'] . "\n";
        file_put_contents("error_log.txt", $logMessage, FILE_APPEND);
    
        // Show countdown and redirect
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Login Failed</title>
            <style>
                body {
                    font-family: 'Segoe UI', sans-serif;
                    background-color: #fefefe;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    text-align: center;
                    flex-direction: column;
                    color: #444;
                }
                .message {
                    font-size: 1.4em;
                    margin-bottom: 20px;
                }
                .countdown {
                    font-size: 2em;
                    color: #007bff;
                }
            </style>
        </head>
        <body>
            <div class='message'>❌ Invalid username or password!<br>Redirecting to login page in <span class='countdown'>3</span> seconds...</div>
            <script>
                let count = 3;
                const countdownEl = document.querySelector('.countdown');
                const timer = setInterval(() => {
                    count--;
                    countdownEl.textContent = count;
                    if (count <= 0) {
                        clearInterval(timer);
                        window.location.href = 'index.html';
                    }
                }, 1000);
            </script>
        </body>
        </html>";
        exit();
    }
}    
?>
