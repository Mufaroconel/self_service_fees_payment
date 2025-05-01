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

// Fetch fees information
$stmt = $conn->prepare("SELECT total_fee, balance FROM fees WHERE user_id = ?");
$stmt->execute([$user_id]);
$fees = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate payment progress
$total_paid = $fees ? ($fees['total_fee'] - $fees['balance']) : 0;
$payment_percentage = $fees && $fees['total_fee'] > 0 ? 
    round(($total_paid / $fees['total_fee']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Balance - Student Fees Portal</title>
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

        .balance-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .balance-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .balance-card h3 {
            color: #4a5568;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .balance-amount {
            font-size: 2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .balance-label {
            color: #718096;
            font-size: 0.875rem;
        }

        .progress-container {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-title {
            color: #4a5568;
            font-size: 1rem;
            font-weight: 500;
        }

        .progress-percentage {
            color: #3182ce;
            font-weight: 600;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #edf2f7;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #3182ce;
            transition: width 0.3s ease;
        }

        .progress-details {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            color: #718096;
            font-size: 0.875rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-button {
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .primary-button {
            background-color: #3182ce;
            color: #fff;
        }

        .primary-button:hover {
            background-color: #2c5282;
        }

        .secondary-button {
            background-color: #edf2f7;
            color: #4a5568;
        }

        .secondary-button:hover {
            background-color: #e2e8f0;
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

            .balance-container {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-button {
                width: 100%;
                justify-content: center;
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
                <a href="check_balance.php" class="nav-item active">
                    <i class="fas fa-wallet"></i>
                    Check Balance
                </a>
                <a href="student_notification.php" class="nav-item">
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
            <div class="header">
                <h1 class="welcome-text">Fee Balance</h1>
            </div>

            <div class="balance-container">
                <div class="balance-card">
                    <h3><i class="fas fa-money-bill-wave"></i> Total Fee</h3>
                    <div class="balance-amount">
                        ZWL <?php echo number_format($fees['total_fee'] ?? 0, 2); ?>
                    </div>
                    <div class="balance-label">Total amount to be paid</div>
                </div>

                <div class="balance-card">
                    <h3><i class="fas fa-wallet"></i> Payment Status</h3>
                    <div class="balance-amount">
                        <?php
                            if ($fees) {
                                if ($fees['balance'] > 0) {
                                    echo 'Outstanding: ZWL ' . number_format($fees['balance'], 2);
                                } elseif ($fees['balance'] < 0) {
                                    echo 'Excess Payment: ZWL ' . number_format(abs($fees['balance']), 2);
                                } else {
                                    echo 'Fully Paid';
                                }
                            } else {
                                echo 'No Fee Information Available';
                            }
                        ?>
                    </div>
                    <div class="balance-label">
                        <?php
                            if ($fees) {
                                if ($fees['balance'] > 0) {
                                    echo 'You still owe this amount.';
                                } elseif ($fees['balance'] < 0) {
                                    echo 'You have overpaid by this amount.';
                                } else {
                                    echo 'All fees are cleared.';
                                }
                            }
                        ?>
                    </div>
                </div>


                <div class="balance-card">
                    <h3><i class="fas fa-checkf-circle"></i> Amount Paid</h3>
                    <div class="balance-amount">
                        ZWL <?php echo number_format($total_paid, 2); ?>
                    </div>
                    <div class="balance-label">Total amount paid so far</div>
                </div>
            </div>

            <div class="progress-container">
                <div class="progress-header">
                    <div class="progress-title">Payment Progress</div>
                    <div class="progress-percentage"><?php echo $payment_percentage; ?>%</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $payment_percentage; ?>%"></div>
                </div>
                <div class="progress-details">
                    <span>Paid: ZWL <?php echo number_format($total_paid, 2); ?></span>
                    <span>Total: ZWL <?php echo number_format($fees['total_fee'] ?? 0, 2); ?></span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="make_payment.php" class="action-button primary-button">
                    <i class="fas fa-money-bill-wave"></i>
                    Make Payment
                </a>
                <a href="payment_history.php" class="action-button secondary-button">
                    <i class="fas fa-history"></i>
                    View Payment History
                </a>
            </div>
        </main>
    </div>
</body>
</html>
