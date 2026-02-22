<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Get today's sales
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as order_count, SUM(total_amount) as total_sales FROM bills WHERE DATE(bill_date) = ?");
$stmt->execute([$today]);
$todaySales = $stmt->fetch();

// Get monthly sales
$month = date('Y-m');
$stmt = $pdo->prepare("SELECT SUM(total_amount) as monthly_sales FROM bills WHERE bill_date LIKE ?");
$stmt->execute([$month . '%']);
$monthlySales = $stmt->fetch();

// Get inventory value
$stmt = $pdo->prepare("SELECT SUM(quantity * cost_price) as inventory_value FROM inventory_items");
$stmt->execute();
$inventoryValue = $stmt->fetch();

// Get staff count
$stmt = $pdo->prepare("SELECT COUNT(*) as staff_count FROM staff WHERE status = 'active'");
$stmt->execute();
$staffCount = $stmt->fetch();

// Get recent bills
$stmt = $pdo->prepare("SELECT b.*, u.name as created_by_name 
                      FROM bills b 
                      LEFT JOIN users u ON b.created_by = u.id 
                      ORDER BY b.bill_date DESC LIMIT 5");
$stmt->execute();
$recentBills = $stmt->fetchAll();

// Get low stock items
$stmt = $pdo->prepare("SELECT i.*, c.name as category_name 
                      FROM inventory_items i 
                      LEFT JOIN categories c ON i.category_id = c.id 
                      WHERE i.quantity <= i.reorder_level 
                      ORDER BY i.quantity ASC LIMIT 5");
$stmt->execute();
$lowStockItems = $stmt->fetchAll();

// Get sales data for chart
$stmt = $pdo->prepare("SELECT DATE(bill_date) as date, SUM(total_amount) as total 
                      FROM bills 
                      WHERE bill_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                      GROUP BY DATE(bill_date) 
                      ORDER BY date");
$stmt->execute();
$salesData = $stmt->fetchAll();

// Format sales data for chart
$dates = [];
$totals = [];
foreach ($salesData as $data) {
    $dates[] = date('M d', strtotime($data['date']));
    $totals[] = $data['total'];
}

// Include header
include_once 'includes/header.php';
?>

<h1 class="page-title">Dashboard</h1>

<!-- Stats Cards -->
<div class="dashboard-cards">
    <div class="stat-card">
        <i class="fas fa-shopping-cart"></i>
        <h3>₹<?php echo number_format($todaySales['total_sales'] ?? 0, 2); ?></h3>
        <p>Today's Sales</p>
    </div>
    
    <div class="stat-card">
        <i class="fas fa-calendar-alt"></i>
        <h3>₹<?php echo number_format($monthlySales['monthly_sales'] ?? 0, 2); ?></h3>
        <p>Monthly Sales</p>
    </div>
    
    <div class="stat-card">
        <i class="fas fa-boxes"></i>
        <h3>₹<?php echo number_format($inventoryValue['inventory_value'] ?? 0, 2); ?></h3>
        <p>Inventory Value</p>
    </div>
    
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <h3><?php echo $staffCount['staff_count'] ?? 0; ?></h3>
        <p>Active Staff</p>
    </div>
</div>

<!-- Charts -->
<div class="row">
    <div class="card">
        <div class="card-header">
            Sales Trend (Last 7 Days)
        </div>
        <div class="card-body">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Bills -->
<div class="row">
    <div class="card">
        <div class="card-header">
            <a href="bill_history.php">
                    <i class="fas fa-history" ></i>
            Bill History
            </a>
        </div>
        <style>
            .card-header a {
                text-decoration: none;
                color: white;
            }
            .card-header a:hover {
                text-decoration: underline;
            }
        </style>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentBills)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No recent bills found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBills as $bill): ?>
                            <tr>
                                <td><?php echo $bill['bill_number']; ?></td>
                                <td><?php echo $bill['customer_name']; ?></td>
                                <td><?php echo number_format($bill['total_amount'], 2); ?></td>
                                <td><?php echo ucfirst($bill['payment_method']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($bill['bill_date'])); ?></td>
                                <td><?php echo $bill['created_by_name']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Low Stock Items -->
<div class="row">
    <div class="card">
        <div class="card-header">
            Low Stock Items
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockItems)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No low stock items found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lowStockItems as $item): ?>
                            <tr>
                                <td><?php echo $item['name']; ?></td>
                                <td><?php echo $item['category_name']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo $item['reorder_level']; ?></td>
                                <td><?php echo $item['unit']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Initialize sales chart
document.addEventListener('DOMContentLoaded', function() {
    const salesChart = document.getElementById('salesChart');
    
    if (salesChart) {
        new Chart(salesChart, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Sales',
                    data: <?php echo json_encode($totals); ?>,
                    borderColor: 'green',
                    backgroundColor: 'rgba(21, 126, 19)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>