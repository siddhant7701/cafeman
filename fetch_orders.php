<?php
require_once 'config/db.php';

$platform = $_GET['platform'];

$stmt = $pdo->prepare("SELECT * FROM online_orders 
    WHERE platform=? 
    ORDER BY id DESC");
$stmt->execute([$platform]);

echo json_encode($stmt->fetchAll());