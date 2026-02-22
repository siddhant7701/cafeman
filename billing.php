<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new bill
    if (isset($_POST['create_bill'])) {
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $payment_method = $_POST['payment_method'];
        $subtotal = $_POST['subtotal'];
        $tax_amount = $_POST['tax_amount'];
        $discount_amount = $_POST['discount_amount'] ?? 0;
        $total_amount = $_POST['total_amount'];
        
        // Validate input
        if (empty($customer_name) || empty($payment_method) || empty($total_amount)) {
            setFlashMessage('error', 'Please fill in all required fields');
            header('Location: billing.php');
            exit();
        }
        function getLastBillNumber($conn) {
            $today = date('Ymd');
            $sql = "SELECT bill_number FROM bills WHERE bill_number LIKE ? ORDER BY bill_number DESC LIMIT 1";
            $like = "BILL-" . $today . "-%";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $like);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                return (int)substr($row['bill_number'], -4);
            }
            return 0; // start from 0 if none found
        }
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Generate bill number
            $bill_number = 'BILL-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Create bill
            $stmt = $pdo->prepare("INSERT INTO bills (bill_number, customer_name, customer_phone, subtotal, tax_amount, discount_amount, total_amount, payment_method, bill_date, created_by) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([$bill_number, $customer_name, $customer_phone, $subtotal, $tax_amount, $discount_amount, $total_amount, $payment_method, $_SESSION['user_id']]);
            
            $bill_id = $pdo->lastInsertId();
            
            // Add bill items
            if (isset($_POST['item_id']) && !empty($_POST['item_id'])) {
                $item_ids = $_POST['item_id'];
                $item_prices = $_POST['item_price'];
                $item_quantities = $_POST['item_quantity'];
                
                for ($i = 0; $i < count($item_ids); $i++) {
                    $total_price = $item_prices[$i] * $item_quantities[$i];
                    
                    $stmt = $pdo->prepare("INSERT INTO bill_items (bill_id, menu_item_id, quantity, unit_price, total_price) 
                                          VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$bill_id, $item_ids[$i], $item_quantities[$i], $item_prices[$i], $total_price]);
                    
                    // Deduct ingredients from inventory
                    $stmt = $pdo->prepare("SELECT i.inventory_item_id, i.quantity as recipe_quantity 
                                          FROM menu_item_ingredients i 
                                          WHERE i.menu_item_id = ?");
                    $stmt->execute([$item_ids[$i]]);
                    $ingredients = $stmt->fetchAll();
                    
                    foreach ($ingredients as $ingredient) {
                        $usage_quantity = $ingredient['recipe_quantity'] * $item_quantities[$i];
                        
                        // Update inventory
                        $stmt = $pdo->prepare("UPDATE inventory_items SET quantity = quantity - ? WHERE id = ?");
                        $stmt->execute([$usage_quantity, $ingredient['inventory_item_id']]);
                        
                        // Add transaction record
                        $stmt = $pdo->prepare("INSERT INTO inventory_transactions (inventory_item_id, transaction_type, quantity, transaction_date, notes, created_by) 
                                              VALUES (?, 'usage', ?, NOW(), ?, ?)");
                        $stmt->execute([$ingredient['inventory_item_id'], $usage_quantity, "Used in Bill #$bill_number", $_SESSION['user_id']]);
                    }
                }
            }
            
            $pdo->commit();
            setFlashMessage('success', 'Bill created successfully');
            
            // Redirect to bill view/print page
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('error', 'Error creating bill: ' . $e->getMessage());
            header('Location: billing.php');
            exit();
        }
    }
}

// Get menu categories
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE type = 'menu' ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching categories: ' . $e->getMessage());
    $categories = [];
}

// Get menu items
try {
    $stmt = $pdo->prepare("SELECT m.*, c.name as category_name 
                          FROM menu_items m 
                          LEFT JOIN categories c ON m.category_id = c.id 
                          WHERE m.is_available = 1 
                          ORDER BY c.name, m.name");
    $stmt->execute();
    $menuItems = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching menu items: ' . $e->getMessage());
    $menuItems = [];
}

// Include header
include_once 'includes/header.php';
?>

<h1 class="page-title">Billing</h1>

<div class="billing-container">
    <!-- Menu Section -->
    <div class="menu-section">
        <h2>Menu Items</h2>
        
        <div class="menu-categories">
            <button type="button" class="category-btn active" data-category="all">All</button>
            <?php foreach ($categories as $category): ?>
                <button type="button" class="category-btn" data-category="<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="menu-items">
            <?php foreach ($menuItems as $item): ?>
                <div class="menu-item" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>" data-price="<?php echo $item['price']; ?>" data-category="<?php echo $item['category_id']; ?>">
                    <?php if ($item['image']): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/50" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                    <?php endif; ?>
                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="item-price"><?php echo number_format($item['price'], 2); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
        .menu-item {
    width: 150px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 10px;
    overflow: hidden;
    text-align: center;
    margin: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.item-image {
    width: 100%;
    height: 100px;
    object-fit: cover; /* keeps aspect ratio, fills box, crops overflow */
    border-radius: 8px;
    display: block;
}

    </style>
    <!-- Bill Section -->
    <div class="bill-section">
        <h2>Current Bill</h2>
        
        <form action="billing.php" method="post" id="bill-form">
            <div class="form-group">
                <label for="customer_name" class="form-label">Customer Name</label>
                <input type="text" id="customer_name" name="customer_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="customer_phone" class="form-label">Customer Phone (Optional)</label>
                <input type="text" id="customer_phone" name="customer_phone" class="form-control">
            </div>
            
            <div class="bill-items">
                <h3>Items</h3>
                <div class="bill-items-container" id="bill-content">
                    <!-- Bill items will be added here dynamically -->
                </div>
            </div>
            
            <div class="bill-total">
                <div class="row">
                    <div class="col-md-6">Subtotal:</div>
                    <div class="col-md-6 text-right" id="subtotal">0.00</div>
                    <input type="hidden" id="subtotal-input" name="subtotal" value="0">
                </div>
                <div class="row">
                    <div class="col-md-6">Tax (5%):</div>
                    <div class="col-md-6 text-right" id="tax-amount">0.00</div>
                    <input type="hidden" id="tax-input" name="tax_amount" value="0">
                </div>
                <div class="row">
                    <div class="col-md-6">Discount:</div>
                    <div class="col-md-6">
                        <input type="number" id="discount-amount" name="discount_amount" class="form-control" value="0" min="0" step="0.01" onchange="updateBillTotals()">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Total:</strong></div>
                    <div class="col-md-6 text-right"><strong id="total-amount">0.00</strong></div>
                    <input type="hidden" id="total-input" name="total_amount" value="0">
                </div>
            </div>
            
            <div class="form-group">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select id="payment_method" name="payment_method" class="form-control" required>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="upi">UPI</option>
                </select>
            </div>
            
            <div class="bill-actions">
                <button type="button" class="btn btn-danger" onclick="clearBill()">Clear</button>
                <button type="submit" name="create_bill" class="btn btn-success">Create Bill</button>
            </div>
        </form>
    </div>
</div>

<script>
// Initialize billing functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.menu-item');
    const billItemsContainer = document.querySelector('.bill-items-container');
    const categoryButtons = document.querySelectorAll('.category-btn');
    
    // Category filter
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Update active button
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter menu items
            menuItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Add menu item to bill
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            
            // Check if item already exists in bill
            const existingItem = document.querySelector(`.bill-item[data-id="${id}"]`);
            
            if (existingItem) {
                // Update quantity
                const quantityInput = existingItem.querySelector('.item-quantity');
                const currentQuantity = parseInt(quantityInput.value);
                quantityInput.value = currentQuantity + 1;
                
                // Update price
                const itemTotal = existingItem.querySelector('.item-total');
                itemTotal.textContent = (price * (currentQuantity + 1)).toFixed(2);
                
                // Update hidden input
                existingItem.querySelector('input[name="item_quantity[]"]').value = currentQuantity + 1;
            } else {
                // Create new bill item
                const billItem = document.createElement('div');
                billItem.classList.add('bill-item');
                billItem.dataset.id = id;
                billItem.dataset.price = price;
                
                billItem.innerHTML = `
                    <div class="row">
                        <div class="col-md-5">${name}</div>
                        <div class="col-md-2">${price.toFixed(2)}</div>
                        <div class="col-md-2">
                            <input type="number" class="item-quantity form-control" value="1" min="1" onchange="updateBillTotals()">
                        </div>
                        <div class="col-md-2 item-total">${price.toFixed(2)}</div>
                        <div class="col-md-1">
                            <button type="button" class="btn-remove" onclick="removeItem(this)">×</button>
                        </div>
                    </div>
                    <input type="hidden" name="item_id[]" value="${id}">
                    <input type="hidden" name="item_price[]" value="${price}">
                    <input type="hidden" name="item_quantity[]" value="1">
                `;
                
                billItemsContainer.appendChild(billItem);
            }
            
            updateBillTotals();
        });
    });
});

// Update bill totals
function updateBillTotals() {
    const billItems = document.querySelectorAll('.bill-item');
    const subtotalElement = document.getElementById('subtotal');
    const taxElement = document.getElementById('tax-amount');
    const totalElement = document.getElementById('total-amount');
    const discountInput = document.getElementById('discount-amount');
    
    let subtotal = 0;
    
    billItems.forEach(item => {
        const price = parseFloat(item.dataset.price);
        const quantity = parseInt(item.querySelector('.item-quantity').value);
        const itemTotal = price * quantity;
        
        item.querySelector('.item-total').textContent = itemTotal.toFixed(2);
        item.querySelector('input[name="item_quantity[]"]').value = quantity;
        
        subtotal += itemTotal;
    });
    
    const taxRate = 0.05; // 5% tax
    const tax = subtotal * taxRate;
    const discount = parseFloat(discountInput.value) || 0;
    const total = subtotal + tax - discount;
    
    subtotalElement.textContent = subtotal.toFixed(2);
    taxElement.textContent = tax.toFixed(2);
    totalElement.textContent = total.toFixed(2);
    
    document.getElementById('subtotal-input').value = subtotal.toFixed(2);
    document.getElementById('tax-input').value = tax.toFixed(2);
    document.getElementById('total-input').value = total.toFixed(2);
}

// Remove item from bill
function removeItem(button) {
    const billItem = button.closest('.bill-item');
    billItem.remove();
    updateBillTotals();
}

// Clear bill
function clearBill() {
    const billItemsContainer = document.querySelector('.bill-items-container');
    billItemsContainer.innerHTML = '';
    
    document.getElementById('customer_name').value = '';
    document.getElementById('customer_phone').value = '';
    document.getElementById('discount-amount').value = '0';
    
    updateBillTotals();
}
</script>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>