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

// Fetch all payments with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total number of payments for pagination
$stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_payments = $stmt->fetchColumn();
$total_pages = ceil($total_payments / $per_page);

// Fetch payments for current page - Fixed the LIMIT and OFFSET syntax
$stmt = $conn->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY paid_at DESC LIMIT ?, ?");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->bindValue(3, $per_page, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Student Fees Portal</title>
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

        .payment-history {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .payment-history h3 {
            margin-bottom: 1.5rem;
            color: #2d3748;
            font-weight: 600;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .payments-table th,
        .payments-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .payments-table th {
            font-weight: 500;
            color: #718096;
            font-size: 0.875rem;
            background-color: #f7fafc;
        }

        .payments-table tr:last-child td {
            border-bottom: none;
        }

        .payments-table tr:hover {
            background-color: #f7fafc;
        }

        .payment-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .payment-link:hover {
            background-color: #f7fafc;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background-color: #edf2f7;
        }

        .pagination .active {
            background-color: #3182ce;
            color: #fff;
            border-color: #3182ce;
        }

        .pagination .disabled {
            color: #a0aec0;
            pointer-events: none;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-paid {
            background-color: #c6f6d5;
            color: #2f855a;
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

            .payments-table {
                display: block;
                overflow-x: auto;
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
                <a href="payment_history.php" class="nav-item active">
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
                <h1 class="welcome-text">Payment History</h1>
            </div>

            <div class="payment-history">
                <h3>All Payments</h3>
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
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No payments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="payment-link">
                                            <?php echo date('d M Y', strtotime($payment['paid_at'])); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="payment-link">
                                            ZWL <?php echo number_format($payment['amount'], 2); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="payment-link">
                                            <?php echo htmlspecialchars($payment['id']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="payment-link">
                                            <span class="status-badge status-paid">Paid</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                        <?php else: ?>
                            <a class="disabled">&laquo; Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" <?php echo $i === $page ? 'class="active"' : ''; ?>>
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                        <?php else: ?>
                            <a class="disabled">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
