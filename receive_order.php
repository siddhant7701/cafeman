<?php
require_once 'config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("INSERT INTO online_orders
(platform, external_order_id, customer_name, amount, status)
VALUES (?, ?, ?, ?, ?)");

$stmt->execute([
    $data['platform'],
    $data['order_id'],
    $data['customer'],
    $data['amount'],
    'confirmed'
]);

echo "OK";