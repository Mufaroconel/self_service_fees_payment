<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$error = '';
$success = '';

// Handle fee setting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $program = $_POST['program'];
    $program_fee = $_POST['program_fee'];
    $accommodation_fee = $_POST['accommodation_fee'];

    try {
        $conn->beginTransaction();

        // Check if program already exists
        $stmt = $conn->prepare("SELECT id FROM fees_structure WHERE program = ?");
        $stmt->execute([$program]);
        if ($stmt->rowCount() > 0) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE fees_structure SET program_fee = ?, accommodation_fee = ? WHERE program = ?");
            $stmt->execute([$program_fee, $accommodation_fee, $program]);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO fees_structure (program, program_fee, accommodation_fee) VALUES (?, ?, ?)");
            $stmt->execute([$program, $program_fee, $accommodation_fee]);
        }

        $conn->commit();
        $success = "✅ Fees set successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "❌ Failed to set fees: " . $e->getMessage();
    }
}

// Fetch existing fees
$stmt = $conn->prepare("SELECT * FROM fees_structure");
$stmt->execute();
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- (same head section as your current page, styles and links) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Fees - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Same styles as before (copy your styles here)... */
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

        .form-container {
            max-width: 600px;
            margin: 0 auto 2rem;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .submit-button {
            background-color: #3182ce;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .submit-button:hover {
            background-color: #2c5282;
        }

        .message {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .success {
            background-color: #c6f6d5;
            color: #2f855a;
        }

        .error {
            background-color: #fed7d7;
            color: #c53030;
        }

        table {
            width: 100%;
            background: #fff;
            border-collapse: collapse;
            margin-top: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        table thead {
            background-color: #3182ce;
            color: #fff;
        }

        table th, table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        table tr:hover {
            background-color: #f1f5f9;
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
                <a href="set_fees.php" class="nav-item active">
                    <i class="fas fa-money-check-alt"></i>
                    Set Fees
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
                <h1 class="welcome-text">Set Program & Accommodation Fees</h1>
            </div>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="message success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="program">Program</label>
                        <select id="program" name="program" required>
                            <option value="">-- Select Program --</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Accounting">Accounting</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Law">Law</option>
                            <!-- You can add more programs here -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="program_fee">Program Fee (USD)</label>
                        <input type="number" id="program_fee" name="program_fee" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="accommodation_fee">Accommodation Fee (USD)</label>
                        <input type="number" id="accommodation_fee" name="accommodation_fee" step="0.01" required>
                    </div>

                    <button type="submit" class="submit-button">Save Fees</button>
                </form>
            </div>

            <div style="margin-top: 3rem;">
                <h2 style="margin-bottom: 1rem; font-size: 1.5rem; font-weight: 600;">Current Fees Table</h2>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #3182ce; color: #fff;">
                                <th style="padding: 0.75rem; text-align: left;">Program</th>
                                <th style="padding: 0.75rem; text-align: left;">Program Fee (USD)</th>
                                <th style="padding: 0.75rem; text-align: left;">Accommodation Fee (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($fees) > 0): ?>
                                <?php foreach ($fees as $fee): ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($fee['program']); ?></td>
                                        <td style="padding: 0.75rem;"><?php echo number_format($fee['program_fee'], 2); ?></td>
                                        <td style="padding: 0.75rem;"><?php echo number_format($fee['accommodation_fee'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="padding: 1rem; text-align: center;">No fees set yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

