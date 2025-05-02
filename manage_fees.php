<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

// Fetch all students with their fees information
$stmt = $conn->prepare("
    SELECT s.id, s.fullname, s.level, s.accommodation, f.total_fee, f.balance
    FROM students s
    LEFT JOIN fees f ON s.user_id = f.user_id
    ORDER BY s.fullname
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fees - Admin Dashboard</title>
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

        .action-button {
            background-color: #3182ce;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .action-button:hover {
            background-color: #2c5282;
        }

        .table-container {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background-color: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }

        tr:hover {
            background-color: #f7fafc;
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

        .status-pending {
            background-color: #fefcbf;
            color: #975a16;
        }

        .action-link {
            color: #3182ce;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-link:hover {
            color: #2c5282;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
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

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
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
                <a href="admin_dashboard.php" class="nav-item">
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
                <a href="manage_fees.php" class="nav-item active">
                    <i class="fas fa-money-bill-wave"></i>
                    Manage Fees
                </a>
                <!-- <a href="payment_history.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    Payment History
                </a>     -->
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1 class="welcome-text">Manage Student Fees</h1>
                <a href="set_fees.php" class="action-button">
                    <i class="fas fa-plus"></i>
                    Set New Fees
                </a>
            </div>

            <div class="table-container">
                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users fa-3x" style="margin-bottom: 1rem; color: #cbd5e0;"></i>
                        <p>No students found. Create a student account first.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Level</th>
                                <th>Accommodation</th>
                                <th>Total Fee</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($student['level']); ?></td>
                                    <td><?php echo htmlspecialchars($student['accommodation']); ?></td>
                                    <td>ZWL <?php echo number_format($student['total_fee'] ?? 0, 2); ?></td>
                                    <td>ZWL <?php echo number_format($student['balance'] ?? 0, 2); ?></td>
                                    <td>
                                        <?php if (($student['balance'] ?? 0) <= 0): ?>
                                            <span class="status-badge status-paid">Paid</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_fee.php?id=<?php echo $student['id']; ?>" class="action-link">
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
