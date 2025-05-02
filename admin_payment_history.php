<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

// Initialize filter and search variables
$filter_name = isset($_GET['name']) ? $_GET['name'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Construct query with optional filters and search
$query = "
    SELECT p.id, s.fullname, p.amount, p.paid_at
    FROM payments p
    LEFT JOIN students s ON p.user_id = s.user_id
    WHERE 1
";

// Filter by student name
if ($filter_name) {
    $query .= " AND s.fullname LIKE :name";
}

// Filter by payment date
if ($filter_date) {
    $query .= " AND DATE(p.paid_at) = :date";
}

// Search by student name or amount
if ($search_query) {
    $query .= " AND (s.fullname LIKE :search OR p.amount LIKE :search)";
}

$query .= " ORDER BY p.paid_at DESC";

$stmt = $conn->prepare($query);

// Bind parameters for filtering and searching
if ($filter_name) {
    $stmt->bindValue(':name', '%' . $filter_name . '%');
}

if ($filter_date) {
    $stmt->bindValue(':date', $filter_date);
}

if ($search_query) {
    $stmt->bindValue(':search', '%' . $search_query . '%');
}

$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments - Admin Dashboard</title>
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
            background-color: #f1f5f9;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #4a5568;
        }

        .empty-state i {
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        .action-link {
            color: #3182ce;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-link:hover {
            color: #2c5282;
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Portal</h2>
            </div>
            <nav class="nav-menu">
                <!-- Sidebar links -->
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

                <a href="manage_fees.php" class="nav-item">
                    <i class="fas fa-money-bill-wave"></i>
                    Manage Fees
                </a>
                <a href="admin_payment_history.php" class="nav-item active">
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
                <h1 class="welcome-text">Payment History</h1>
                <a href="record_payment.php" class="action-button">
                    <i class="fas fa-plus"></i> Record Payment
                </a>
            </div>
            <!-- Print Button -->
            <div class="print-button">
                <button onclick="printTable()" class="action-button">
                    <i class="fas fa-print"></i> Print Payment History
                </button>
            </div>

            <!-- Filter form -->
            <div class="filter-form">
                <form method="get" action="">
                    <input type="text" name="name" placeholder="Filter by Student Name" value="<?php echo htmlspecialchars($filter_name); ?>">
                    <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                    <button type="submit">Filter</button>
                </form>
            </div>

            <!-- Search form -->
            <div class="search-form">
                <form method="get" action="">
                    <input type="text" name="search" placeholder="Search by Student Name or Amount" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Payments table -->
            <table id="payments-table" class="payments-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Amount Paid</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                                <td><?php echo htmlspecialchars($payment['paid_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">No payments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
<script>
        function printTable() {
            var printContent = document.getElementById('payments-table').outerHTML;
            var printWindow = window.open('', '', 'height=400,width=600');
            printWindow.document.write('<html><head><title>Payment History</title></head><body>');
            printWindow.document.write(printContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</html>

