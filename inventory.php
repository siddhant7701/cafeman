<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new inventory item
    if (isset($_POST['add_item'])) {
        $name = trim($_POST['name']);
        $category_id = $_POST['category_id'];
        $quantity = $_POST['quantity'];
        $unit = trim($_POST['unit']);
        $cost_price = $_POST['cost_price'];
        $reorder_level = $_POST['reorder_level'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory_items (name, category_id, quantity, unit, cost_price, reorder_level) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category_id, $quantity, $unit, $cost_price, $reorder_level]);
            
            // Add transaction record
            $item_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO inventory_transactions (inventory_item_id, transaction_type, quantity, unit_price, total_price, transaction_date, notes, created_by) 
                                  VALUES (?, 'purchase', ?, ?, ?, NOW(), 'Initial stock', ?)");
            $total_price = $quantity * $cost_price;
            $stmt->execute([$item_id, $quantity, $cost_price, $total_price, $_SESSION['user_id']]);
            
            setFlashMessage('success', 'Inventory item added successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding inventory item: ' . $e->getMessage());
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    // Edit inventory item
    if (isset($_POST['edit_item'])) {
        $item_id = $_POST['item_id'];
        $name = trim($_POST['name']);
        $category_id = $_POST['category_id'];
        $unit = trim($_POST['unit']);
        $cost_price = $_POST['cost_price'];
        $reorder_level = $_POST['reorder_level'];
        
        try {
            $stmt = $pdo->prepare("UPDATE inventory_items SET name = ?, category_id = ?, unit = ?, 
                                  cost_price = ?, reorder_level = ? WHERE id = ?");
            $stmt->execute([$name, $category_id, $unit, $cost_price, $reorder_level, $item_id]);
            
            setFlashMessage('success', 'Inventory item updated successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating inventory item: ' . $e->getMessage());
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    // Delete inventory item
    if (isset($_POST['delete_item'])) {
        $item_id = $_POST['item_id'];
        
        try {
            // Check if item is used in menu items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_item_ingredients WHERE inventory_item_id = ?");
            $stmt->execute([$item_id]);
            $usageCount = $stmt->fetchColumn();
            
            if ($usageCount > 0) {
                setFlashMessage('error', 'Cannot delete item: It is used in menu recipes');
            } else {
                // Start transaction
                $pdo->beginTransaction();
                
                // Delete transactions
                $stmt = $pdo->prepare("DELETE FROM inventory_transactions WHERE inventory_item_id = ?");
                $stmt->execute([$item_id]);
                
                // Delete inventory item
                $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = ?");
                $stmt->execute([$item_id]);
                
                // Commit transaction
                $pdo->commit();
                
                setFlashMessage('success', 'Inventory item deleted successfully');
            }
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlashMessage('error', 'Error deleting inventory item: ' . $e->getMessage());
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    // Add inventory transaction
    if (isset($_POST['add_transaction'])) {
        $item_id = $_POST['item_id'];
        $transaction_type = $_POST['transaction_type'];
        $quantity = $_POST['quantity'];
        $unit_price = $_POST['unit_price'];
        $total_price = $quantity * $unit_price;
        $notes = trim($_POST['notes']);
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Add transaction record
            $stmt = $pdo->prepare("INSERT INTO inventory_transactions (inventory_item_id, transaction_type, quantity, unit_price, total_price, transaction_date, notes, created_by) 
                                  VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->execute([$item_id, $transaction_type, $quantity, $unit_price, $total_price, $notes, $_SESSION['user_id']]);
            
            // Update inventory quantity
            if ($transaction_type == 'purchase') {
                $stmt = $pdo->prepare("UPDATE inventory_items SET quantity = quantity + ? WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE inventory_items SET quantity = quantity - ? WHERE id = ?");
            }
            $stmt->execute([$quantity, $item_id]);
            
            $pdo->commit();
            setFlashMessage('success', 'Transaction added successfully');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('error', 'Error adding transaction: ' . $e->getMessage());
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    // Add category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (?, 'inventory')");
            $stmt->execute([$name]);
            setFlashMessage('success', 'Category added successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding category: ' . $e->getMessage());
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    // Edit category
    if (isset($_POST['edit_category'])) {
        $category_id = $_POST['category_id'];
        $name = trim($_POST['category_name']);
        
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ? AND type = 'inventory'");
            $stmt->execute([$name, $category_id]);
            setFlashMessage('success', 'Category updated successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating category: ' . $e->getMessage());
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        
        try {
            // Check if category has inventory items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $itemCount = $stmt->fetchColumn();
            
            if ($itemCount > 0) {
                setFlashMessage('error', 'Cannot delete category: It contains inventory items');
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND type = 'inventory'");
                $stmt->execute([$category_id]);
                setFlashMessage('success', 'Category deleted successfully');
            }
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error deleting category: ' . $e->getMessage());
        }
        
        header('Location: inventory.php');
        exit();
    }
}

// Get inventory items
try {
    $stmt = $pdo->prepare("SELECT i.*, c.name as category_name 
                          FROM inventory_items i 
                          LEFT JOIN categories c ON i.category_id = c.id 
                          ORDER BY i.name");
    $stmt->execute();
    $inventoryItems = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching inventory items: ' . $e->getMessage());
    $inventoryItems = [];
}

// Get inventory categories
try {
    $stmt = $pdo->prepare("SELECT c.*, COUNT(i.id) as item_count 
                          FROM categories c 
                          LEFT JOIN inventory_items i ON c.id = i.category_id 
                          WHERE c.type = 'inventory' 
                          GROUP BY c.id
                          ORDER BY c.name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching categories: ' . $e->getMessage());
    $categories = [];
}

// Include header
include_once 'includes/header.php';
?>

<h1 class="page-title">Inventory Management</h1>

<?php echo displayFlashMessage(); ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                Categories
                <button type="button" class="btn" onclick="openModal('addCategoryModal')">
                    <i class="fas fa-folder-plus"></i> Add Category
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Items</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No categories found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo $category['item_count']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($category['item_count'] == 0): ?>
                                                <form method="post" action="" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" disabled title="Cannot delete category with items">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                Inventory Items
                <button type="button" class="btn" onclick="openModal('addItemModal')">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Cost Price</th>
                            <th>Value</th>
                            <th>Reorder Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventoryItems)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No inventory items found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventoryItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><?php echo number_format($item['cost_price'], 2); ?></td>
                                    <td><?php echo number_format($item['quantity'] * $item['cost_price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['reorder_level']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" onclick="window.location.href='inventory_transactions.php?item_id=<?php echo $item['id']; ?>'">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" onclick="addTransaction(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm" onclick="editItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="post" action="" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this inventory item?');">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="delete_item" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal" id="addItemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Inventory Item</h5>
                <button type="button" class="close" onclick="closeModal('addItemModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="inventory.php" method="post">
                    <div class="form-group">
                        <label for="name" class="form-label">Item Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id" class="form-label">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity" class="form-label">Initial Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit" class="form-label">Unit</label>
                        <input type="text" id="unit" name="unit" class="form-control" placeholder="kg, liter, piece, etc." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cost_price" class="form-label">Cost Price</label>
                        <input type="number" id="cost_price" name="cost_price" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reorder_level" class="form-label">Reorder Level</label>
                        <input type="number" id="reorder_level" name="reorder_level" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_item" class="btn btn-block">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal" id="editItemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Inventory Item</h5>
                <button type="button" class="close" onclick="closeModal('editItemModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="inventory.php" method="post">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    
                    <div class="form-group">
                        <label for="edit_name" class="form-label">Item Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_category_id" class="form-label">Category</label>
                        <select id="edit_category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_unit" class="form-label">Unit</label>
                        <input type="text" id="edit_unit" name="unit" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_cost_price" class="form-label">Cost Price</label>
                        <input type="number" id="edit_cost_price" name="cost_price" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_reorder_level" class="form-label">Reorder Level</label>
                        <input type="number" id="edit_reorder_level" name="reorder_level" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="edit_item" class="btn btn-block">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal" id="addCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Category</h5>
                <button type="button" class="close" onclick="closeModal('addCategoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="inventory.php" method="post">
                    <div class="form-group">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_category" class="btn btn-block">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal" id="editCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="close" onclick="closeModal('editCategoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="inventory.php" method="post">
                    <input type="hidden" name="category_id" id="edit_category_id_hidden">
                    <div class="form-group">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" id="edit_category_name" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="edit_category" class="btn btn-block">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal" id="addTransactionModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Transaction</h5>
                <button type="button" class="close" onclick="closeModal('addTransactionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="inventory.php" method="post">
                    <input type="hidden" id="transaction_item_id" name="item_id">
                    
                    <div class="form-group">
                        <label for="transaction_item_name" class="form-label">Item</label>
                        <input type="text" id="transaction_item_name" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_type" class="form-label">Transaction Type</label>
                        <select id="transaction_type" name="transaction_type" class="form-control" required>
                            <option value="purchase">Purchase (Add to Inventory)</option>
                            <option value="usage">Usage (Remove from Inventory)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_quantity" class="form-label">Quantity</label>
                        <input type="number" id="transaction_quantity" name="quantity" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_unit_price" class="form-label">Unit Price</label>
                        <input type="number" id="transaction_unit_price" name="unit_price" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_notes" class="form-label">Notes</label>
                        <textarea id="transaction_notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_transaction" class="btn btn-block">Add Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

// Function to close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Function to open add transaction modal
function addTransaction(itemId, itemName) {
    document.getElementById('transaction_item_id').value = itemId;
    document.getElementById('transaction_item_name').value = itemName;
    
    // Show modal
    openModal('addTransactionModal');
}

// Edit category
function editCategory(categoryId, categoryName) {
    document.getElementById('edit_category_id_hidden').value = categoryId;
    document.getElementById('edit_category_name').value = categoryName;
    openModal('editCategoryModal');
}

// Edit inventory item
function editItem(itemId) {
    // Fetch item details via AJAX
    fetch('get_inventory_item.php?id=' + itemId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.item;
                document.getElementById('edit_item_id').value = item.id;
                document.getElementById('edit_name').value = item.name;
                document.getElementById('edit_category_id').value = item.category_id;
                document.getElementById('edit_unit').value = item.unit;
                document.getElementById('edit_cost_price').value = item.cost_price;
                document.getElementById('edit_reorder_level').value = item.reorder_level;
                
                openModal('editItemModal');
            } else {
                alert('Error loading inventory item: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading inventory item data.');
        });
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    document.querySelectorAll('.modal').forEach(modal => {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
});
</script>

<style>
/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-dialog {
    margin: 50px auto;
    max-width: 600px;
}

.modal-content {
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #ddd;
}

.modal-body {
    padding: 15px;
}

.close {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}
</style>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>