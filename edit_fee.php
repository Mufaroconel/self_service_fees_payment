<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$error = '';
$success = '';

// Get student ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch student information
$stmt = $conn->prepare("
    SELECT s.*, f.total_fee, f.balance
    FROM students s
    LEFT JOIN fees f ON s.user_id = f.user_id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: manage_fees.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $total_fee = $_POST['total_fee'];
    $balance = $_POST['balance'];

    try {
        // Check if record exists
        $stmt = $conn->prepare("SELECT * FROM fees WHERE user_id = ?");
        $stmt->execute([$student['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE fees SET total_fee = ?, balance = ? WHERE user_id = ?");
            $stmt->execute([$total_fee, $balance, $student['user_id']]);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO fees (user_id, total_fee, balance) VALUES (?, ?, ?)");
            $stmt->execute([$student['user_id'], $total_fee, $balance]);
        }
        
        $success = "✅ Fees updated successfully!";
        
        // Refresh student data
        $stmt = $conn->prepare("
            SELECT s.*, f.total_fee, f.balance
            FROM students s
            LEFT JOIN fees f ON s.user_id = f.user_id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "❌ Failed to update fees: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fees - Admin Dashboard</title>
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

        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .student-info {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .student-info h3 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .student-info p {
            color: #718096;
            margin-bottom: 0.25rem;
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

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .currency-prefix {
            position: relative;
        }

        .currency-prefix input {
            padding-left: 3rem;
        }

        .currency-prefix::before {
            content: 'ZWL';
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            font-weight: 500;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .submit-button {
            background-color: #3182ce;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            flex: 1;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .submit-button:hover {
            background-color: #2c5282;
        }

        .cancel-button {
            background-color: #edf2f7;
            color: #4a5568;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            flex: 1;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .cancel-button:hover {
            background-color: #e2e8f0;
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

            .button-group {
                flex-direction: column;
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
                <a href="manage_fees.php" class="nav-item active">
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
                <h1 class="welcome-text">Edit Student Fees</h1>
            </div>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="message success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="student-info">
                    <h3><?php echo htmlspecialchars($student['fullname']); ?></h3>
                    <p>Level: <?php echo htmlspecialchars($student['level']); ?></p>
                    <p>Accommodation: <?php echo htmlspecialchars($student['accommodation']); ?></p>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="total_fee">Total Fee</label>
                        <div class="currency-prefix">
                            <input type="number" id="total_fee" name="total_fee" step="0.01" value="<?php echo $student['total_fee'] ?? 0; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="balance">Balance</label>
                        <div class="currency-prefix">
                            <input type="number" id="balance" name="balance" step="0.01" value="<?php echo $student['balance'] ?? 0; ?>" required>
                        </div>
                    </div>

                    <div class="button-group">
                        <a href="manage_fees.php" class="cancel-button">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                        <button type="submit" class="submit-button">
                            <i class="fas fa-save"></i>
                            Update Fees
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>