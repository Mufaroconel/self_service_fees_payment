<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if ($_SESSION['role'] != 'student') {
    header("Location: index.html");
    exit();
}

require 'db.php';

// Fetch student information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password'])) {
        $message = "Current password is incorrect.";
        $messageType = "error";
    } elseif ($new !== $confirm) {
        $message = "New passwords do not match.";
        $messageType = "error";
    } elseif (strlen($new) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "error";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        $message = "Password updated successfully.";
        $messageType = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Student Fees Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #1a1f36;
            color: #fff;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .student-info {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .student-info p {
            color: #a0aec0;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .student-info .student-name {
            color: #fff;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .student-info .student-id {
            color: #a0aec0;
            font-size: 0.875rem;
        }

        .nav-menu {
            padding: 1.5rem 0;
        }

        .nav-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            color: #a0aec0;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .nav-item.active {
            background-color: #3182ce;
            color: #fff;
        }

        .nav-item i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .welcome-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .password-form-container {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .submit-button {
            width: 100%;
            padding: 0.75rem;
            background-color: #3182ce;
            color: #fff;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-button:hover {
            background-color: #2c5282;
        }

        .message {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 0.375rem;
            text-align: center;
            font-weight: 500;
        }

        .message.error {
            background-color: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .message.success {
            background-color: #f0fff4;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }

        .password-requirements {
            margin-top: 1.5rem;
            padding: 1rem;
            background-color: #f7fafc;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #4a5568;
        }

        .password-requirements h4 {
            margin-bottom: 0.5rem;
            color: #2d3748;
            font-weight: 500;
        }

        .password-requirements ul {
            list-style-type: none;
            padding-left: 0;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-requirements li i {
            color: #718096;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .password-form-container {
                margin: 0 1rem;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Student Portal</h2>
            </div>
            <div class="student-info">
                <p class="student-name"><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></p>
                <p class="student-id">ID: <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></p>
            </div>
            <nav class="nav-menu">
                <a href="student_dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="make_payment.php" class="nav-item">
                    <i class="fas fa-money-bill-wave"></i>
                    Make Payment
                </a>
                <a href="payment_history.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    Payment History
                </a>
                <a href="check_balance.php" class="nav-item">
                    <i class="fas fa-wallet"></i>
                    Check Balance
                </a>
                <a href="change_password.php" class="nav-item active">
                    <i class="fas fa-key"></i>
                    Change Password
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1 class="welcome-text">Change Password</h1>
            </div>

            <div class="password-form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="submit-button">
                        <i class="fas fa-key"></i> Update Password
                    </button>
                </form>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php if ($messageType === 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php endif; ?>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="password-requirements">
                    <h4>Password Requirements</h4>
                    <ul>
                        <li><i class="fas fa-check"></i> At least 8 characters long</li>
                        <li><i class="fas fa-check"></i> Include a mix of letters and numbers</li>
                        <li><i class="fas fa-check"></i> Use a unique password not used elsewhere</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
