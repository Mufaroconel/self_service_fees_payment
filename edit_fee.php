<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$user_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    die("Invalid user ID.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $total = $_POST['total_fee'];
    $balance = $_POST['balance'];

    $stmt = $conn->prepare("SELECT * FROM fees WHERE user_id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        $stmt = $conn->prepare("UPDATE fees SET total_fee = ?, balance = ? WHERE user_id = ?");
        $stmt->execute([$total, $balance, $user_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO fees (user_id, total_fee, balance) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $total, $balance]);
    }

    $message = "✅ Fee updated!";
}

$stmt = $conn->prepare("SELECT * FROM fees WHERE user_id = ?");
$stmt->execute([$user_id]);
$fee = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Fees</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h2 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }
        label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Fees</h2>

    <?php if (isset($message)): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="total_fee">Total Fee:</label>
        <input type="number" id="total_fee" name="total_fee" step="0.01" min="0" value="<?= htmlspecialchars($fee['total_fee'] ?? 0) ?>" required>

        <label for="balance">Balance:</label>
        <input type="number" id="balance" name="balance" step="0.01" min="0" value="<?= htmlspecialchars($fee['balance'] ?? 0) ?>" required>

        <button type="submit">Update</button>
    </form>

    <a class="back-link" href="manage_fees.php">⬅ Back</a>
</div>

</body>
</html>
