<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM reservations WHERE id=?");
$stmt->execute([$id]);
$res = $stmt->fetch();

if (!$res) {
    setFlashMessage('error', 'Reservation not found');
    header("Location: reservations.php");
    exit();
}

if (isset($_POST['update_reservation'])) {

    $stmt = $pdo->prepare("UPDATE reservations SET
        customer_name=?,
        phone=?,
        persons=?,
        table_no=?,
        reservation_date=?,
        reservation_time=?
        WHERE id=?");

    $stmt->execute([
        $_POST['customer_name'],
        $_POST['phone'],
        $_POST['persons'],
        $_POST['table_no'],
        $_POST['reservation_date'],
        $_POST['reservation_time'],
        $id
    ]);

    setFlashMessage('success', 'Reservation Updated');
    header("Location: reservations.php");
    exit();
}

include_once 'includes/header.php';
?>

<h1>Edit Reservation</h1>

<form method="POST" class="card p-4">

<input type="text" name="customer_name" value="<?= $res['customer_name'] ?>" required>
<input type="text" name="phone" value="<?= $res['phone'] ?>">
<input type="number" name="persons" value="<?= $res['persons'] ?>" required>
<input type="number" name="table_no" value="<?= $res['table_no'] ?>" required>
<input type="date" name="reservation_date" value="<?= $res['reservation_date'] ?>" required>
<input type="time" name="reservation_time" value="<?= $res['reservation_time'] ?>" required>

<button name="update_reservation" class="btn btn-success">
Update
</button>

</form>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>