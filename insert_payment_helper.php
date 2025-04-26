<?php
require_once 'db.php';

function insertPayment(int $user_id, float $amount, string $paynow_guid, string $poll_url, string $paid_at): array {
    global $conn;

    // Prepare SQL statement
    $stmt = $conn->prepare("
        INSERT INTO payments (user_id, amount, paynow_guid, poll_url, paid_at)
        VALUES (:user_id, :amount, :paynow_guid, :poll_url, :paid_at)
    ");

    if (!$stmt) {
        return [
            'success' => false,
            'message' => "Prepare failed: " . $conn->errorInfo()
        ];
    }

    // Bind the parameters
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);  // Use PDO::PARAM_STR for float values
    $stmt->bindValue(':paynow_guid', $paynow_guid, PDO::PARAM_STR);
    $stmt->bindValue(':poll_url', $poll_url, PDO::PARAM_STR);
    $stmt->bindValue(':paid_at', $paid_at, PDO::PARAM_STR);

    // Execute statement
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => "Payment successfully inserted!"
        ];
    } else {
        return [
            'success' => false,
            'message' => "Execute failed: " . implode(" | ", $stmt->errorInfo())
        ];
    }
}

?>
