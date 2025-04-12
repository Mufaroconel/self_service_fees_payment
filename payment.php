<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];

    // Update balance
    $stmt = $conn->prepare("UPDATE fees SET balance = balance - ? WHERE user_id = ?");
    $stmt->execute([$amount, $user_id]);

    // Record payment
    $stmt = $conn->prepare("INSERT INTO payments (user_id, amount) VALUES (?, ?)");
    $stmt->execute([$user_id, $amount]);

    echo "<div class='success'>✅ Payment recorded!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make a Payment</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 60px;
        }

        .payment-form {
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }

        h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
            transition: border-color 0.2s;
        }

        input[type="number"]:focus {
            border-color: #007bff;
            outline: none;
        }

        button {
            width: 100%;
            background-color: #007bff;
            color: white;
            padding: 12px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .success {
            margin-top: 20px;
            color: green;
            font-weight: bold;
        }

        .back-link {
            margin-top: 20px;
            display: inline-block;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <form class="payment-form" method="POST">
        <h2>Make a Payment</h2>
        <label for="amount">Amount to Pay ($):</label>
        <input type="number" step="0.01" name="amount" id="amount" placeholder="Enter amount" required>
        <button type="submit">Submit Payment</button>
    </form>

    <a href="student_dashboard.php" class="back-link">⬅ Back to Dashboard</a>

</body>
</html>

