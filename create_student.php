<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$error = '';
$success = '';

function getFees($program, $level) {
    global $conn;

    // Check if the fee structure exists
    $stmt = $conn->prepare("SELECT program_fee, accommodation_fee FROM fees_structure WHERE program = ? AND semester = ?");
    $stmt->execute([$program, $level]);

    $fees = $stmt->fetch();

    if (!$fees) {
        throw new Exception("❌ Fee structure not found for this program ($program), level ($level), and semester ($semester). Student not registered.");
    }

    $program_fee = $fees['program_fee'];
    $accommodation_fee = $accommodation ? $fees['accommodation_fee'] : 0;

    return $program_fee + $accommodation_fee;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_number = $_POST['reg_number'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $level = $_POST['level'];
    $program = $_POST['program'];
    $accommodation = $_POST['accommodation'];
    // $semester = $_POST['semester'];
    $role = "student";
    $password = password_hash('default_password', PASSWORD_DEFAULT); // You can change this logic

    try {
        // First, ensure fee structure exists BEFORE doing anything else
        $total_fee = getFees($program, $level, $semester, $accommodation); // Will throw exception if not found

        // Now check if the student already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE reg_number = ?");
        $stmt->execute([$reg_number]);
        $studentExists = $stmt->fetchColumn();

        if ($studentExists) {
            $error = "❌ Student with registration number $reg_number already exists.";
        } else {
            $conn->beginTransaction();

            // Create user
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$reg_number, $password, $role]);
            $user_id = $conn->lastInsertId();

            // Create student
            $stmt = $conn->prepare("INSERT INTO students (user_id, reg_number, fullname, email, phone, level, program, accommodation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $reg_number, $fullname, $email, $phone, $level, $program, $accommodation]);

            // Create fees record
            $amount_paid = 0;
            $balance = $total_fee;
            $created_at = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO fees (user_id, year, semester, total_fee, amount_paid, balance, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $level, $level, $total_fee, $amount_paid, $balance, $created_at]);

            $conn->commit();
            $success = "✅ Student and fee record created successfully.";
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $e->getMessage(); // This already has the ❌ prefix from getFees() or catch block
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student - Admin Dashboard</title>
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
                <a href="admin_dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="create_student.php" class="nav-item active">
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
                <!-- <a href="payment_history.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    Payment History
                </a> -->
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1 class="welcome-text">Create Student Account</h1>
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
                        <label for="reg_number">Registration Number</label>
                        <input type="text" id="reg_number" name="reg_number" required>
                    </div>

                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label for="level">Level</label>
                        <select id="level" name="level" required>
                            <option value="">Select Level</option>
                            <option value="1.1">1.1</option>
                            <option value="1.2">1.2</option>
                            <option value="2.1">2.1</option>
                            <option value="2.2">2.2</option>
                            <option value="3.1">3.1</option>
                            <option value="3.2">3.2</option>
                            <option value="4.1">4.1</option>
                            <option value="4.2">4.2</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="program">Program</label>
                        <select id="program" name="program" required>
                            <option value="">-- Select Program --</option>
                            <?php
                            // Fetch distinct programs from fees_structure
                            $stmt = $conn->query("SELECT DISTINCT program FROM fees_structure ORDER BY program ASC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value=\"" . htmlspecialchars($row['program']) . "\">" . htmlspecialchars($row['program']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="accommodation">Accommodation</label>
                        <select id="accommodation" name="accommodation" required>
                            <option value="">Select Accommodation</option>
                            <option value="Resident">Resident</option>
                            <option value="Non-Resident">Non-Resident</option>
                        </select>
                    </div>

                    <button type="submit" class="submit-button">Create Student Account</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
