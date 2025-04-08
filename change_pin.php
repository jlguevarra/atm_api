<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$cardNumber = $data['card_number'] ?? '';
$currentPin = $data['current_pin'] ?? '';
$newPin = $data['new_pin'] ?? '';

if (!$cardNumber || !$currentPin || !$newPin) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT pin FROM users WHERE card_number = ?");
    $stmt->bind_param("s", $cardNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Card number not found."]);
        exit;
    }

    $storedPin = $result->fetch_assoc()['pin'];

    if ($storedPin !== $currentPin) {
        echo json_encode(["status" => "error", "message" => "Current PIN is incorrect."]);
        exit;
    }

    if ($storedPin === $newPin) {
        echo json_encode(["status" => "error", "message" => "New PIN must be different from the current PIN."]);
        exit;
    }

    $updateStmt = $conn->prepare("UPDATE users SET pin = ? WHERE card_number = ?");
    $updateStmt->bind_param("ss", $newPin, $cardNumber);
    $updateStmt->execute();

    echo json_encode(["status" => "success", "message" => "PIN successfully changed."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
