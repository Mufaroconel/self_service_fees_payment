<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

require 'db.php'; // Include your DB connection file

$stmt = $conn->query("SELECT COUNT(*) as total FROM students");
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as active FROM students WHERE reg_number IS NOT NULL");
$active_students = $stmt->fetch(PDO::FETCH_ASSOC)['active'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as pending FROM students s LEFT JOIN payments p ON s.user_id = p.user_id WHERE p.user_id IS NULL");
$pending_payments = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;

$stmt = $conn->query("SELECT SUM(amount) as today_total FROM payments WHERE DATE(paid_at) = CURDATE()");
$todays_collections = number_format($stmt->fetch(PDO::FETCH_ASSOC)['today_total'] ?? 0, 2);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Fees Portal</title>
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
                <h2>Admin Portal</h2>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="create_student.php" class="nav-item">
                    <i class="fas fa-user-plus"></i>
                    Create Student
                </a>
                <a href="set_fees.php" class="nav-item">
                    <i class="fas fa-coins"></i> Set Fees
                </a>

                <a href="manage_fees.php" class="nav-item">
                    <i class="fas fa-money-bill-wave"></i>
                    Manage Fees
                </a>
                <a href="payment_history.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    Payment History
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1 class="welcome-text">Welcome, Admin</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="value"><?php echo $total_students; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending Payments</h3>
                    <div class="value"><?php echo $pending_payments; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Today's Collections</h3>
                    <div class="value">ZWL <?php echo $todays_collections; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Students</h3>
                    <div class="value"><?php echo $active_students; ?></div>
                </div>
            </div>

            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="create_student.php" class="action-button">
                        <i class="fas fa-user-plus"></i>
                        Add New Student
                    </a>
                    <a href="manage_fees.php" class="action-button">
                        <i class="fas fa-money-bill-wave"></i>
                        Update Fees
                    </a>
                    <a href="set_fees.php" class="action-button">
                        <i class="fas fa-coins"></i> Set Fees
                    </a>
                    <a href="admin_payment_history.php" class="action-button">
                        <i class="fas fa-history"></i>
                        View Payments
                    </a>
                    <a href="reports.php" class="action-button">
                        <i class="fas fa-file-alt"></i>
                        Generate Report
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
