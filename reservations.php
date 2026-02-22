<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// CREATE
if (isset($_POST['create_reservation'])) {

    $stmt = $pdo->prepare("INSERT INTO reservations 
        (customer_name, phone, persons, table_no, reservation_date, reservation_time, status)
        VALUES (?, ?, ?, ?, ?, ?, 'booked')");

    $stmt->execute([
        $_POST['customer_name'],
        $_POST['phone'],
        $_POST['persons'],
        $_POST['table_no'],
        $_POST['reservation_date'],
        $_POST['reservation_time']
    ]);

    setFlashMessage('success', 'Reservation Created Successfully');
    header("Location: reservations.php");
    exit();
}

// STATUS UPDATE
if (isset($_GET['update_status'])) {
    $stmt = $pdo->prepare("UPDATE reservations SET status=? WHERE id=?");
    $stmt->execute([$_GET['status'], $_GET['update_status']]);

    setFlashMessage('success', 'Reservation Updated');
    header("Location: reservations.php");
    exit();
}

// FETCH ACTIVE BOOKINGS
$stmt = $pdo->prepare("SELECT * FROM reservations 
    WHERE status='booked' 
    ORDER BY reservation_date ASC, reservation_time ASC");
$stmt->execute();
$reservations = $stmt->fetchAll();

include_once 'includes/header.php';
?>

<h1 class="page-title">Active Reservations</h1>

<div class="card p-4 mb-4" style="
    padding: 20px;">

<div class="d-flex justify-between mb-3">
    <h3>Add Reservation</h3>
    <a href="reservation_history.php" class="btn btn-secondary">
        View Reservation History
    </a>
</div>
</br>

<form method="POST" class="grid-2">

<div class="form-group">
<label>Name</label>
<input type="text" name="customer_name" class="form-control" required>
</div>

<div class="form-group">
<label>Phone</label>
<input type="text" name="phone" class="form-control">
</div>

<div class="form-group">
<label>Persons</label>
<input type="number" name="persons" class="form-control" required>
</div>

<div class="form-group">
<label>Table No</label>
<input type="number" name="table_no" class="form-control" required>
</div>

<div class="form-group">
<label>Date</label>
<input type="date" name="reservation_date" class="form-control" required>
</div>

<div class="form-group">
<label>Time</label>
<input type="time" name="reservation_time" class="form-control" required>
</div>

<button type="submit" name="create_reservation" class="btn btn-success">
Create Reservation
</button>

</form>
</div>

<div class="card p-4" style="
    padding: 20px;">
<h3>Upcoming Bookings</h3>

<table class="table table-striped">
<thead>
<tr>
<th>Name</th>
<th>Phone</th>
<th>Persons</th>
<th>Table</th>
<th>Date</th>
<th>Time</th>
<th>Actions</th>
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
<td><?= $r['reservation_time'] ?></td>
<td>

<a href="edit_reservation.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">
Edit
</a>

<a href="reservations.php?update_status=<?= $r['id'] ?>&status=completed" 
class="btn btn-sm btn-success">
Complete
</a>

<a href="reservations.php?update_status=<?= $r['id'] ?>&status=cancelled" 
class="btn btn-sm btn-warning">
Cancel
</a>

</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>