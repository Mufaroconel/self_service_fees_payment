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
$stmt = $conn->prepare("SELECT * FROM fees WHERE user_id = ?");
$stmt->execute([$user_id]);
$fees = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent payments
$stmt = $conn->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY paid_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the last payment date
$last_payment_date = !empty($recent_payments) ? $recent_payments[0]['paid_at'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Fees Portal</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .recent-payments {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .recent-payments h3 {
            margin-bottom: 1rem;
            color: #2d3748;
            font-weight: 600;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payments-table th,
        .payments-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .payments-table th {
            font-weight: 500;
            color: #718096;
            font-size: 0.875rem;
        }

        .payments-table tr:last-child td {
            border-bottom: none;
        }

        .quick-actions {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .quick-actions h3 {
            margin-bottom: 1rem;
            color: #2d3748;
            font-weight: 600;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-button {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .action-button:hover {
            background: #edf2f7;
            transform: translateY(-2px);
        }

        .action-button i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
            color: #3182ce;
        }

        .logout-button {
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            background: #e53e3e;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .logout-button:hover {
            background: #c53030;
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
                <p class="student-id">ID: <?php echo htmlspecialchars($student['id'] ?? 'N/A'); ?></p>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active">
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
                <h1 class="welcome-text">Welcome, <?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Outstanding Balance</h3>
                    <div class="value">ZWL <?php echo number_format($fees['balance'] ?? 0, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Paid</h3>
                    <div class="value">ZWL <?php echo number_format($payment['amount'] ?? 0, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Last Payment</h3>
                    <div class="value"><?php echo $last_payment_date ? date('d M Y', strtotime($last_payment_date)) : 'No payments'; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Payment Status</h3>
                    <div class="value"><?php echo ($fees['balance'] ?? 0) <= 0 ? 'Fully Paid' : 'Pending'; ?></div>
                </div>
            </div>

            <div class="recent-payments">
                <h3>Recent Payments</h3>
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Reference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_payments)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No recent payments</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_payments as $payment): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($payment['paid_at'])); ?></td>
                                    <td>ZWL <?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                    <td>Paid</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="make_payment.php" class="action-button">
                        <i class="fas fa-money-bill-wave"></i>
                        Make Payment
                    </a>
                    <a href="payment_history.php" class="action-button">
                        <i class="fas fa-history"></i>
                        View History
                    </a>
                    <a href="check_balance.php" class="action-button">
                        <i class="fas fa-wallet"></i>
                        Check Balance
                    </a>
                    <a href="change_password.php" class="action-button">
                        <i class="fas fa-key"></i>
                        Change Password
                    </a>
                </div>
            </div>

            <a href="logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </main>
    </div>
</body>
</html>
