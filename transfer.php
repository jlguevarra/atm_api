<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$sender = $data['sender_account'];
$receiver = $data['receiver_account'];
$amount = (float) $data['amount'];
$accountType = $data['account_type'];

$conn->begin_transaction();

try {
    // Get sender balance
    $stmt = $conn->prepare("SELECT balance FROM transactions WHERE card_number = ? AND account_type = ? ORDER BY date DESC LIMIT 1");
    $stmt->bind_param("ss", $sender, $accountType);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Sender account not found.");
    }

    $senderBalance = (float) $result->fetch_assoc()['balance'];

    if ($senderBalance < $amount) {
        throw new Exception("Insufficient balance.");
    }

    // Update sender transaction
    $newSenderBalance = $senderBalance - $amount;
    $stmt = $conn->prepare("INSERT INTO transactions (card_number, date, type, amount, balance, account_type) VALUES (?, NOW(), 'Transfer', ?, ?, ?)");
    $stmt->bind_param("sdds", $sender, $amount, $newSenderBalance, $accountType);
    $stmt->execute();

    // Update sender user balance
    if ($accountType == "Savings") {
        $stmt = $conn->prepare("UPDATE users SET savings_balance = ? WHERE card_number = ?");
    } else {
        $stmt = $conn->prepare("UPDATE users SET current_balance = ? WHERE card_number = ?");
    }
    $stmt->bind_param("ds", $newSenderBalance, $sender);
    $stmt->execute();

    // Get receiver balance
    $stmt = $conn->prepare("SELECT balance FROM transactions WHERE card_number = ? AND account_type = ? ORDER BY date DESC LIMIT 1");
    $stmt->bind_param("ss", $receiver, $accountType);
    $stmt->execute();
    $result = $stmt->get_result();

    $receiverBalance = 0;
    if ($result->num_rows > 0) {
        $receiverBalance = (float) $result->fetch_assoc()['balance'];
    }

    $newReceiverBalance = $receiverBalance + $amount;

    // Update receiver transaction
    $stmt = $conn->prepare("INSERT INTO transactions (card_number, date, type, amount, balance, account_type) VALUES (?, NOW(), 'Transfer', ?, ?, ?)");
    $stmt->bind_param("sdds", $receiver, $amount, $newReceiverBalance, $accountType);
    $stmt->execute();

    // Update receiver user balance
    if ($accountType == "Savings") {
        $stmt = $conn->prepare("UPDATE users SET savings_balance = ? WHERE card_number = ?");
    } else {
        $stmt = $conn->prepare("UPDATE users SET current_balance = ? WHERE card_number = ?");
    }
    $stmt->bind_param("ds", $newReceiverBalance, $receiver);
    $stmt->execute();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Transfer successful."]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
