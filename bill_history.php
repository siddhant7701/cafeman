<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php
include 'config/db.php';
include 'config/session.php';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " AND (b.bill_number LIKE :search OR b.customer_name LIKE :search OR b.customer_phone LIKE :search)";
}

// Date filter
$dateFilter = '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
if (!empty($from_date) && !empty($to_date)) {
    $dateFilter = " AND DATE(b.bill_date) BETWEEN :from_date AND :to_date";
}

// Get total records
$totalQuery = "SELECT COUNT(*) as total FROM bills b WHERE 1=1 $searchCondition $dateFilter";
$totalStmt = $pdo->prepare($totalQuery);
if (!empty($search)) {
    $searchParam = "%$search%";
    $totalStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $totalStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $totalStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
if (!empty($from_date) && !empty($to_date)) {
    $totalStmt->bindParam(':from_date', $from_date, PDO::PARAM_STR);
    $totalStmt->bindParam(':to_date', $to_date, PDO::PARAM_STR);
}
$totalStmt->execute();
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Get bills with pagination
$query = "SELECT b.*, u.name as created_by_name 
          FROM bills b 
          LEFT JOIN users u ON b.created_by = u.id 
          WHERE 1=1 $searchCondition $dateFilter
          ORDER BY b.bill_date DESC 
          LIMIT :start, :limit";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':start', $start, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
if (!empty($from_date) && !empty($to_date)) {
    $stmt->bindParam(':from_date', $from_date, PDO::PARAM_STR);
    $stmt->bindParam(':to_date', $to_date, PDO::PARAM_STR);
}
$stmt->execute();

// Handle delete bill
if (isset($_POST['delete_bill'])) {
    $bill_id = $_POST['bill_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete bill items first
        $deleteItemsStmt = $pdo->prepare("DELETE FROM bill_items WHERE bill_id = :bill_id");
        $deleteItemsStmt->bindParam(':bill_id', $bill_id, PDO::PARAM_INT);
        $deleteItemsStmt->execute();
        
        // Delete bill
        $deleteBillStmt = $pdo->prepare("DELETE FROM bills WHERE id = :bill_id");
        $deleteBillStmt->bindParam(':bill_id', $bill_id, PDO::PARAM_INT);
        $deleteBillStmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Bill deleted successfully";
        header("Location: bill_history.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting bill: " . $e->getMessage();
    }
}

$pageTitle = "Bill History";
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ul class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    </ul>    
    <?php echo displayFlashMessage(); ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            Bill History
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-8">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <input type="date" class="form-control" name="from_date" placeholder="From Date" value="<?php echo htmlspecialchars($from_date); ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" name="to_date" placeholder="To Date" value="<?php echo htmlspecialchars($to_date); ?>">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search bill number, customer..." value="<?php echo htmlspecialchars($search); ?>">
                        </div></br>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div></br>

                    </form>
                </div>
                <div class="col-md-4 text-end">
                    <a href="billing.php" class="btn btn-success"><i class="fas fa-plus"></i> New Bill</a>
</br>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Bill #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stmt->rowCount() > 0): ?>
                            <?php while ($row = $stmt->fetch()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['bill_number']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($row['customer_name']); ?><br>
                                        <small><?php echo htmlspecialchars($row['customer_phone'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo date('d-m-Y', strtotime($row['bill_date'])); ?></td>
                                    <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo strtolower($row['payment_status']) == 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo $row['payment_status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['created_by_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <a href="bill_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this bill?');" style="display:inline-block;">
                                            <input type="hidden" name="bill_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_bill" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No bills found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>