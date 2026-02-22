<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Item ID is required']);
    exit;
}

$item_id = $_GET['id'];

try {
    // Get menu item details
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    // Get ingredients
    $stmt = $pdo->prepare("SELECT * FROM menu_item_ingredients WHERE menu_item_id = ?");
    $stmt->execute([$item_id]);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $item['ingredients'] = $ingredients;
    
    echo json_encode(['success' => true, 'item' => $item]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>