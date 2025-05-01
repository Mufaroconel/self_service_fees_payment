<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.html");
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch student info
$stmt = $conn->prepare("SELECT fullname, id FROM students WHERE id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark all as viewed
$stmt = $conn->prepare("UPDATE notifications SET is_viewed = TRUE WHERE user_id = ? AND is_viewed = FALSE");
$stmt->execute([$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Notifications</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: #1a1f36;
            color: #fff;
            padding: 2rem 1rem;
            height: 100vh;
        }

        .sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .student-info {
            margin-bottom: 2rem;
        }

        .student-info .student-name {
            font-weight: bold;
            font-size: 1.2rem;
        }

        .student-info .student-id {
            color: #a0aec0;
            font-size: 0.875rem;
        }

        .nav-menu .nav-item {
            color: #a0aec0;
            display: block;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            text-decoration: none;
        }

        .nav-item:hover,
        .nav-item.active {
            background-color: #3182ce;
            color: #fff;
            border-radius: 4px;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .header {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }

        .notification {
            background: #fff;
            border-left: 5px solid #3182ce;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .notification.new {
            border-color: #38a169;
            background-color: #f0fff4;
        }

        .notification.viewed {
            opacity: 0.8;
        }

        .notification strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 0.25rem;
            color: #2d3748;
        }

        .notification p {
            margin: 0.25rem 0;
        }

        .notification small {
            color: #718096;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <h2>Student Portal</h2>
        <div class="student-info">
            <p class="student-name"><?= htmlspecialchars($student['fullname']) ?></p>
            <p class="student-id">ID: <?= htmlspecialchars($student['id']) ?></p>
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
                <a href="student_notification.php" class="nav-item active">
                    <i class="fas fa-bell"></i> 
                    Notifications
                </a>

                <a href="change_password.php" class="nav-item">
                    <i class="fas fa-key"></i>
                    Change Password
                </a>
            </nav>
    </aside>

    <main class="main-content">
        <div class="header">Notifications</div>
        <?php if (empty($notifications)): ?>
            <p>No notifications at the moment.</p>
        <?php else: ?>
            <?php foreach ($notifications as $note): ?>
                <div class="notification <?= $note['is_viewed'] ? 'viewed' : 'new' ?>">
                    <strong><?= htmlspecialchars($note['title']) ?></strong>
                    <p><?= htmlspecialchars($note['message']) ?></p>
                    <small><?= date('F j, Y, g:i a', strtotime($note['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
