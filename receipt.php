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

// Get payment ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch student information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch payment details
$stmt = $conn->prepare("
    SELECT p.*, s.fullname as student_name, s.level
    FROM payments p 
    JOIN students s ON p.user_id = s.user_id 
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->execute([$id, $user_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

// If payment not found or doesn't belong to student, redirect to dashboard
if (!$payment) {
    header("Location: student_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - Student Fees Portal</title>
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

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .receipt-header h2 {
            color: #2d3748;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .receipt-header p {
            color: #718096;
            font-size: 0.875rem;
        }

        .receipt-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .detail-group {
            margin-bottom: 1rem;
        }

        .detail-label {
            color: #718096;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            color: #2d3748;
            font-weight: 500;
        }

        .amount-section {
            text-align: center;
            padding: 2rem;
            background-color: #f7fafc;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        .amount-label {
            color: #718096;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .amount-value {
            color: #2d3748;
            font-size: 2rem;
            font-weight: 600;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            color: #718096;
            font-size: 0.875rem;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
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

        .print-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 0.75rem 1.5rem;
            background-color: #3182ce;
            color: #fff;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .print-button:hover {
            background-color: #2c5282;
        }

        @media print {
            .sidebar, .action-buttons, .print-button {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 0;
            }

            .receipt-container {
                box-shadow: none;
            }
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

            .receipt-details {
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
                <p class="student-name"><?php echo htmlspecialchars($student['fullname'] ?? 'Student'); ?></p>
                <p class="student-id">Level: <?php echo htmlspecialchars($student['level'] ?? 'N/A'); ?></p>
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
                <a href="change_password.php" class="nav-item">
                    <i class="fas fa-key"></i>
                    Change Password
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1 class="welcome-text">Payment Receipt</h1>
            </div>

            <div class="receipt-container">
                <div class="receipt-header">
                    <h2>Payment Receipt</h2>
                    <p>Student Fees Payment Portal</p>
                </div>

                <div class="receipt-details">
                    <div class="detail-group">
                        <div class="detail-label">Receipt Number</div>
                        <div class="detail-value"><?php echo str_pad($payment['id'], 8, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Date</div>
                        <div class="detail-value"><?php echo date('d M Y', strtotime($payment['paid_at'])); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Student Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($payment['student_name']); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Level</div>
                        <div class="detail-value"><?php echo htmlspecialchars($payment['level']); ?></div>
                    </div>
                </div>

                <div class="amount-section">
                    <div class="amount-label">Amount Paid</div>
                    <div class="amount-value">ZWL <?php echo number_format($payment['amount'], 2); ?></div>
                </div>

                <div class="receipt-footer">
                    <p>This is an official receipt for your payment.</p>
                    <p>Please keep this receipt for your records.</p>
                </div>

                <div class="action-buttons">
                    <a href="payment_history.php" class="action-button secondary-button">
                        <i class="fas fa-history"></i>
                        Back to Payment History
                    </a>
                    <button onclick="window.print()" class="action-button primary-button">
                        <i class="fas fa-print"></i>
                        Print Receipt
                    </button>
                </div>
            </div>
        </main>
    </div>

    <button onclick="window.print()" class="print-button">
        <i class="fas fa-print"></i>
        Print Receipt
    </button>
</body>
</html>
