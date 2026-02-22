<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Check if bill ID is provided
if (!isset($_GET['bill_id']) || empty($_GET['bill_id'])) {
    setFlashMessage('error', 'Bill ID is required');
    header('Location: billing.php');
    exit();
}

$bill_id = $_GET['bill_id'];

// Get bill details
try {
    $stmt = $pdo->prepare("SELECT b.*, u.name as created_by_name 
                          FROM bills b 
                          LEFT JOIN users u ON b.created_by = u.id 
                          WHERE b.id = ?");
    $stmt->execute([$bill_id]);
    $bill = $stmt->fetch();

    if (!$bill) {
        setFlashMessage('error', 'Bill not found');
        header('Location: billing.php');
        exit();
    }

    // Get bill items
    $stmt = $pdo->prepare("SELECT bi.*, m.name as item_name 
                          FROM bill_items bi 
                          LEFT JOIN menu_items m ON bi.menu_item_id = m.id 
                          WHERE bi.bill_id = ?");
    $stmt->execute([$bill_id]);
    $billItems = $stmt->fetchAll();

    // Get GST number if available
    $stmt = $pdo->prepare("SELECT gst_number FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $gst_number = $user['gst_number'] ?? '';
} catch (PDOException $e) {
    setFlashMessage('error', 'Error retrieving bill: ' . $e->getMessage());
    header('Location: billing.php');
    exit();
}

// Include header
include_once 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Bill Receipt</h2>
                        <div>
                            <button onclick="printBill()" class="btn btn-info">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <a href="billing.php" class="btn">
                                <i class="fas fa-arrow-left"></i> Back to Billing
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body" id="bill-content">
                    <div class="bill-header text-center">
                        <h2>CafeMan</h2>
                        <!-- <p>Cafe Management System</p> -->
                        <?php if (!empty($gst_number)): ?>
                            <p>GST Number: <?php echo htmlspecialchars($gst_number); ?></p>
                        <?php endif; ?>
                        <hr>
                    </div>
                    
                    <div class="bill-info">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Bill Number:</strong> <?php echo htmlspecialchars($bill['bill_number']); ?></p>
                                <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($bill['bill_date'])); ?></p>
                            </div>
                            <div class="col-md-6 text-right">
                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($bill['customer_name']); ?></p>
                                <?php if (!empty($bill['customer_phone'])): ?>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($bill['customer_phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr>
                    </div>
                    
                    <div class="bill-items">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($billItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-right"><?php echo number_format($item['total_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="bill-total">
                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <table class="table">
                                    <tr>
                                        <td>Subtotal:</td>
                                        <td class="text-right"><?php echo number_format($bill['subtotal'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tax (5%):</td>
                                        <td class="text-right"><?php echo number_format($bill['tax_amount'], 2); ?></td>
                                    </tr>
                                    <?php if ($bill['discount_amount'] > 0): ?>
                                        <tr>
                                            <td>Discount:</td>
                                            <td class="text-right"><?php echo number_format($bill['discount_amount'], 2); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Total:</strong></td>
                                        <td class="text-right"><strong><?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bill-footer">
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Payment Method:</strong> <?php echo ucfirst($bill['payment_method']); ?></p>
                            </div>
                            <div class="col-md-6 text-right">
                                <p><strong>Served by:</strong> <?php echo htmlspecialchars($bill['created_by_name']); ?></p>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <p>Thank you for your visit!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {

    const tableContent = document.querySelector('.table').outerHTML;

    const printWindow = window.open('', '', 'width=1000,height=700');

    printWindow.document.write(`
        <html>
        <head>
            <style>
                body { font-family: Arial; padding:20px; }
                table { width:100%; border-collapse: collapse; }
                th, td { border:1px solid #ddd; padding:8px; }
                th { background:#f4f4f4; }
                .text-right { text-align:right; }
            </style>
        </head>
        <body>
            <h1>${reportTitle}</h1>
            ${tableContent}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>