<?php
require_once 'db.php';

function getTotalFee(int $user_id): array {
    global $conn;

    try {
        // Query to get the total fee and balance for the student
        $stmt = $conn->prepare("SELECT total_fee, balance FROM fees WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch and return the result
        $fee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fee) {
            throw new Exception("No fee record found for user_id: $user_id");
        }

        return [
            'success' => true,
            'data' => $fee
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error querying total fee: " . $e->getMessage()
        ];
    }
}

// Function to calculate the new balance after payment
function calculateNewBalance(float $current_balance, float $payment_amount): float {
    return $current_balance - $payment_amount;
}

// Function to update the balance in the fees table
function updateBalance(int $user_id, float $new_balance): array {
    global $conn;

    try {
        // Query to update the balance in the fees table
        $stmt = $conn->prepare("UPDATE fees SET balance = :new_balance WHERE user_id = :user_id");
        $stmt->bindValue(':new_balance', $new_balance, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => "Balance successfully updated!"
            ];
        } else {
            throw new Exception("Error executing the update query.");
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error updating balance: " . $e->getMessage()
        ];
    }
}

?>
