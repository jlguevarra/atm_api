<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$cardNumber = $data['card_number'];
$accountType = $data['account_type'];

try {
    $stmt = $conn->prepare("SELECT balance FROM transactions WHERE card_number = ? AND account_type = ? ORDER BY date DESC LIMIT 1");
    $stmt->bind_param("ss", $cardNumber, $accountType);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Account not found."]);
        exit;
    }

    $balance = $result->fetch_assoc()['balance'];
    echo json_encode(["status" => "success", "balance" => $balance]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
