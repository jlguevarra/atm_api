<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$cardNumber = $data['card_number'];
$amount = (float) $data['amount'];
$accountType = $data['account_type'];

$conn->begin_transaction();

try {
    // Get latest balance from transactions
    $stmt = $conn->prepare("SELECT balance FROM transactions WHERE card_number = ? AND account_type = ? ORDER BY date DESC LIMIT 1");
    $stmt->bind_param("ss", $cardNumber, $accountType);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Account not found.");
    }

    $currentBalance = (float) $result->fetch_assoc()['balance'];

    if ($currentBalance < $amount) {
        throw new Exception("Insufficient balance.");
    }

    $newBalance = $currentBalance - $amount;

    // Log transaction
    $stmt = $conn->prepare("INSERT INTO transactions (card_number, date, type, amount, balance, account_type) VALUES (?, NOW(), 'Withdraw', ?, ?, ?)");
    $stmt->bind_param("sdds", $cardNumber, $amount, $newBalance, $accountType);
    $stmt->execute();

    // Update user balance
    if ($accountType == "Savings") {
        $stmt = $conn->prepare("UPDATE users SET savings_balance = ? WHERE card_number = ?");
    } else {
        $stmt = $conn->prepare("UPDATE users SET current_balance = ? WHERE card_number = ?");
    }
    $stmt->bind_param("ds", $newBalance, $cardNumber);
    $stmt->execute();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Withdrawal successful."]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
