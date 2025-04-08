<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$pin = $data['pin'] ?? null;

if (!$pin) {
    echo json_encode(["status" => "error", "message" => "PIN is required."]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT card_number FROM users WHERE pin = ? LIMIT 1");
    $stmt->bind_param("s", $pin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Invalid PIN."]);
        exit;
    }

    $cardNumber = $result->fetch_assoc()['card_number'];

    echo json_encode(["status" => "success", "card_number" => $cardNumber]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
