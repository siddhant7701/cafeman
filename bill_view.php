<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php
include 'config/db.php';
include 'config/session.php';

// Check if bill ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Bill ID is required";
    header("Location: bill_history.php");
    exit();
}

$bill_id = (int)$_GET['id'];

// Get bill details
$billQuery = "SELECT b.*, u.name as created_by_name 
              FROM bills b 
              LEFT JOIN users u ON b.created_by = u.id 
              WHERE b.id = :bill_id";
$billStmt = $pdo->prepare($billQuery);
$billStmt->bindParam(':bill_id', $bill_id, PDO::PARAM_INT);
$billStmt->execute();

if ($billStmt->rowCount() == 0) {
    $_SESSION['error'] = "Bill not found";
    header("Location: bill_history.php");
    exit();
}

$bill = $billStmt->fetch();

// Get bill items
$itemsQuery = "SELECT bi.*, mi.name as item_name 
               FROM bill_items bi 
               LEFT JOIN menu_items mi ON bi.item_id = mi.id 
               WHERE bi.bill_id = :bill_id";
$itemsStmt = $pdo->prepare($itemsQuery);
$itemsStmt->bindParam(':bill_id', $bill_id, PDO::PARAM_INT);
// $itemsStmt->execute();

$pageTitle = "View Bill: " . $bill['bill_number'];
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="bill_history.php">Bill History</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php echo displayFlashMessage(); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-file-invoice me-1"></i>
                        Bill Details
                    </div>
                    <div>
                        <button onclick="window.print();" class="btn btn-sm btn-primary">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <a href="bill_history.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="mb-3">Bill Information:</h6>
                            <div><strong>Bill Number:</strong> <?php echo htmlspecialchars($bill['bill_number']); ?></div>
                            <div><strong>Date:</strong> <?php echo date('d-m-Y H:i', strtotime($bill['bill_date'])); ?></div>
                            <div><strong>Created By:</strong> <?php echo htmlspecialchars($bill['created_by_name'] ?? 'Unknown'); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="mb-3">Customer Information:</h6>
                            <div><strong>Name:</strong> <?php echo htmlspecialchars($bill['customer_name']); ?></div>
                            <?php if (!empty($bill['customer_phone'])): ?>
                                <div><strong>Phone:</strong> <?php echo htmlspecialchars($bill['customer_phone']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                while ($item = $itemsStmt->fetch()): 
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td>₹<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-4 col-sm-5 ms-auto">
                            <table class="table table-clear">
                                <tbody>
                                    <tr>
                                        <td class="left"><strong>Subtotal</strong></td>
                                        <td class="right">₹<?php echo number_format($bill['subtotal'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="left"><strong>Tax</strong></td>
                                        <td class="right">₹<?php echo number_format($bill['tax_amount'], 2); ?></td>
                                    </tr>
                                    <?php if ($bill['discount_amount'] > 0): ?>
                                    <tr>
                                        <td class="left"><strong>Discount</strong></td>
                                        <td class="right">₹<?php echo number_format($bill['discount_amount'], 2); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="left"><strong>Total</strong></td>
                                        <td class="right"><strong>₹<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="left"><strong>Payment Method</strong></td>
                                        <td class="right"><?php echo htmlspecialchars($bill['payment_method']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="left"><strong>Status</strong></td>
                                        <td class="right">
                                            <span class="badge bg-<?php echo strtolower($bill['payment_status']) == 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo $bill['payment_status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-cog me-1"></i>
                    Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if (strtolower($bill['payment_status']) != 'paid'): ?>
                        <a href="update_bill_status.php?id=<?php echo $bill_id; ?>&status=paid" class="btn btn-success">
                            <i class="fas fa-check-circle"></i> Mark as Paid
                        </a>
                        <?php endif; ?>
                        
                        <form method="POST" action="bill_history.php" onsubmit="return confirm('Are you sure you want to delete this bill?');">
                            <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                            <button type="submit" name="delete_bill" class="btn btn-danger w-100">
                                <i class="fas fa-trash-alt"></i> Delete Bill
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- You can add more cards here with additional information if needed -->
        </div>
    </div>
</div>

<style>
    @media print {
        .breadcrumb, .card-header, .col-lg-4, footer, .navbar, .sb-sidenav {
            display: none !important;
        }
        .container-fluid {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .card {
            border: none !important;
        }
        h1 {
            font-size: 18px !important;
            text-align: center;
            margin-bottom: 20px !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>