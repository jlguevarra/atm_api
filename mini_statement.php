<?php
header('Content-Type: application/json');
include 'db.php';

$card_number = $_GET['card_number'];

$sql = "SELECT date, type, amount, balance, account_type FROM transactions WHERE card_number = ? ORDER BY date DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $card_number);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];

while ($row = $result->fetch_assoc()) {
    $row['date'] = date('c', strtotime($row['date']));
    $row['amount'] = (float) $row['amount'];   // Cast to number
    $row['balance'] = (float) $row['balance']; // Cast to number
    $row['account_type'] = (string) $row['account_type']; // force as string
    $transactions[] = $row;
}


echo json_encode($transactions);
?>
