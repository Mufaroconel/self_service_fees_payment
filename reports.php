<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

// Fetch total fees, payments, and balance
$stmt = $conn->prepare("SELECT SUM(total_fee) AS total_fee, SUM(amount_paid) AS total_paid, SUM(balance) AS total_balance FROM fees");
$stmt->execute();
$feesSummary = $stmt->fetch();

// Fetch excess payments
$stmtExcess = $conn->prepare("SELECT SUM(excess_amount) AS total_excess FROM fees_excess");
$stmtExcess->execute();
$excessSummary = $stmtExcess->fetch();

// Fetch student fee data
$stmtStudents = $conn->prepare("SELECT s.fullname, f.total_fee, f.amount_paid, f.balance FROM fees f INNER JOIN students s ON f.user_id = s.user_id");
$stmtStudents->execute();
$studentsData = $stmtStudents->fetchAll();

// Fetch payments history
$stmtPayments = $conn->prepare("SELECT s.fullname, p.amount, p.paid_at FROM payments p INNER JOIN students s ON p.user_id = s.user_id");
$stmtPayments->execute();
$paymentsHistory = $stmtPayments->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Financial Reports</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fb;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            padding: 40px;
            color: #333;
        }
        h2 {
            text-align: center;
            color: #4E5D6C;
            margin-bottom: 40px;
        }
        .summary-boxes {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .summary-box {
            background-color: #007BFF;
            color: white;
            padding: 20px;
            border-radius: 10px;
            flex: 1;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .summary-box h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .summary-box p {
            font-size: 24px;
            font-weight: bold;
        }
        .summary-box1 {
            background-color: #28A745;
        }
        .summary-box2 {
            background-color: #FFC107;
        }
        .summary-box3 {
            background-color: #DC3545;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            font-size: 16px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            text-decoration: none;
            color: #007BFF;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .print-btn {
            background-color: #007BFF;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 0 auto;
        }
        .print-btn:hover {
            background-color: #0056b3;
        }
        .charts {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .chart-container {
            width: 48%;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Advanced Financial Reports</h2>

    <!-- Summary Section -->
    <div class="summary-boxes">
        <div class="summary-box summary-box1">
            <h4>Total Fees</h4>
            <p><?= number_format($feesSummary['total_fee'], 2) ?></p>
        </div>
        <div class="summary-box summary-box2">
            <h4>Total Paid</h4>
            <p><?= number_format($feesSummary['total_paid'], 2) ?></p>
        </div>
        <div class="summary-box summary-box3">
            <h4>Total Balance</h4>
            <p><?= number_format($feesSummary['total_balance'], 2) ?></p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts">
        <div class="chart-container">
            <canvas id="feesChart" width="400" height="200"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="excessChart" width="400" height="200"></canvas>
        </div>
    </div>

    <script>
        // Bar Chart: Total Fees vs Amount Paid
        var ctx1 = document.getElementById('feesChart').getContext('2d');
        var feesChart = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: ['Total Fees', 'Amount Paid', 'Balance'],
                datasets: [{
                    label: 'Fee Summary',
                    data: [<?= $feesSummary['total_fee'] ?>, <?= $feesSummary['total_paid'] ?>, <?= $feesSummary['total_balance'] ?>],
                    backgroundColor: ['#007BFF', '#28A745', '#DC3545'],
                    borderColor: ['#007BFF', '#28A745', '#DC3545'],
                    borderWidth: 1
                }]
            }
        });

        // Pie Chart: Excess Payments Distribution
        var ctx2 = document.getElementById('excessChart').getContext('2d');
        var excessChart = new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: ['Excess Payments'],
                datasets: [{
                    data: [<?= $excessSummary['total_excess'] ?>],
                    backgroundColor: ['#FFC107'],
                    borderWidth: 1
                }]
            }
        });
    </script>


    <h3>Payments History</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Amount Paid</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($paymentsHistory as $payment): ?>
                <tr>
                    <td><?= htmlspecialchars($payment['fullname']) ?></td>
                    <td><?= number_format($payment['amount'], 2) ?></td>
                    <td><?= $payment['paid_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Excess Payments Report</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Excess Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($studentsData as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['fullname']) ?></td>
                    <td><?= number_format($excessSummary['total_excess'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button class="print-btn" onclick="window.print()">Print Report</button>

    <a class="back-link" href="admin_dashboard.php">⬅ Back to Dashboard</a>
</div>

</body>
</html>
