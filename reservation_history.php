<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$query = "SELECT * FROM reservations WHERE status!='booked'";

$params = [];

if ($search) {
    $query .= " AND customer_name LIKE ?";
    $params[] = "%$search%";
}

if ($status) {
    $query .= " AND status=?";
    $params[] = $status;
}

$query .= " ORDER BY reservation_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

include_once 'includes/header.php';
?>

<h1>Reservation History</h1>

<form method="GET" class="mb-3">

<input type="text" name="search" placeholder="Search by Name" value="<?= htmlspecialchars($search) ?>">

<select name="status">
<option value="">All</option>
<option value="completed">Completed</option>
<option value="cancelled">Cancelled</option>
</select>

<button class="btn btn-primary">Search</button>

<a href="reservations.php" class="btn btn-secondary">
Back to Active Reservations
</a>
</form>
</br>

<table class="table table-bordered">
<thead>
<tr>
<th>Name</th>
<th>Phone</th>
<th>Persons</th>
<th>Table</th>
<th>Date</th>
<th>Status</th>
</tr>
</thead>

<tbody>
<?php foreach($reservations as $r): ?>
<tr>
<td><?= htmlspecialchars($r['customer_name']) ?></td>
<td><?= htmlspecialchars($r['phone']) ?></td>
<td><?= $r['persons'] ?></td>
<td><?= $r['table_no'] ?></td>
<td><?= $r['reservation_date'] ?></td>
<td><?= ucfirst($r['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>