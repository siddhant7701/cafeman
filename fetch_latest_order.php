<?php
require_once 'config/db.php';

$stmt=$pdo->query("SELECT * FROM online_orders ORDER BY id DESC LIMIT 1");
echo json_encode($stmt->fetch());