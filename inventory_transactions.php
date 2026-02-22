<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Check if item ID is provided
if (!isset($_GET['item_id']) || empty($_GET['item_id'])) {
    setFlashMessage('error', 'Item ID is required');
    header('Location: inventory.php');
    exit();
}

$item_id = $_GET['item_id'];

// Get item details
$stmt = $pdo->prepare("SELECT i.*, c.name as category_name 
                      FROM inventory_items i 
                      LEFT JOIN categories c ON i.category_id = c.id 
                      WHERE i.id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    setFlashMessage('error', 'Item not found');
    header('Location: inventory.php');
    exit();
}

// Get transactions for this item
$stmt = $pdo->prepare("SELECT t.*, u.name as created_by_name 
                      FROM inventory_transactions t 
                      LEFT JOIN users u ON t.created_by = u.id 
                      WHERE t.inventory_item_id = ? 
                      ORDER BY t.transaction_date DESC");
$stmt->execute([$item_id]);
$transactions = $stmt->fetchAll();

// Include header
include_once 'includes/header.php';
?>

<h1 class="page-title">Inventory Transactions</h1>

<div class="row">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Transactions for <?php echo $item['name']; ?></h2>
                <a href="inventory.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Inventory
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="item-details mb-4">
                <h3>Item Details</h3>
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Name:</strong> <?php echo $item['name']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Category:</strong> <?php echo $item['category_name']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Current Stock:</strong> <?php echo $item['quantity'] . ' ' . $item['unit']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Cost Price:</strong> <?php echo number_format($item['cost_price'], 2); ?></p>
                    </div>
                </div>
            </div>
            
            <h3>Transaction History</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                        <th>Notes</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No transactions found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $transaction['transaction_type'] == 'purchase' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo $transaction['quantity'] . ' ' . $item['unit']; ?></td>
                                <td><?php echo isset($transaction['unit_price']) ? number_format($transaction['unit_price'], 2) : '-'; ?></td>
                                <td><?php echo isset($transaction['total_price']) ? number_format($transaction['total_price'], 2) : '-'; ?></td>
                                <td><?php echo $transaction['notes']; ?></td>
                                <td><?php echo $transaction['created_by_name']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>