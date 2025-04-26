<?php
require_once 'db.php';

function insertPaymentInitiation(int $user_id, float $amount): array {
    global $conn;

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO payments_initiated (user_id, amount, status, created_at) VALUES (?, ?, ?, NOW())");

    if (!$stmt) {
        // Error preparing the statement
        return [
            'success' => false,
            'message' => "Prepare failed: (" . $conn->errno . ") " . $conn->error
        ];
    }

    $initial_status = "initiated";

    // Bind the parameters using PDO's bindValue method
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $amount, PDO::PARAM_STR);  // Amount is a float, so we bind as a string
    $stmt->bindValue(3, $initial_status, PDO::PARAM_STR);

    // Execute the statement
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => "Payment initiation recorded successfully!"
        ];
    } else {
        // Error executing the statement
        return [
            'success' => false,
            'message' => "Execute failed: (" . $stmt->errorCode() . ") " . $stmt->errorInfo()[2]
        ];
    }
}
?>
