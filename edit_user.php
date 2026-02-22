<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireAdmin();

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit();
}

if (isset($_POST['update_user'])) {

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET name=?, role=?, password=? WHERE id=?")
            ->execute([$_POST['name'], $_POST['role'], $password, $id]);
    } else {
        $pdo->prepare("UPDATE users SET name=?, role=? WHERE id=?")
            ->execute([$_POST['name'], $_POST['role'], $id]);
    }

    setFlashMessage('success', 'User Updated');
    header("Location: users.php");
    exit();
}

include_once 'includes/header.php';
?>

<h1 class="page-title">Edit User</h1>

<div class="card" style="max-width: 600px; margin: 30px auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">

<form method="POST">

    <div class="form-group" style="margin-bottom: 20px;">
        <label style="font-weight: 600; display: block; margin-bottom: 6px;">Full Name</label>
        <input 
            type="text" 
            name="name" 
            value="<?= htmlspecialchars($user['name']) ?>" 
            class="form-control"
            style="padding: 10px; border-radius: 8px;"
            required>
    </div>

    <div class="form-group" style="margin-bottom: 20px;">
        <label style="font-weight: 600; display: block; margin-bottom: 6px;">Role</label>
        <select 
            name="role" 
            class="form-control"
            style="padding: 10px; border-radius: 8px;">

            <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
            <option value="manager" <?= $user['role']=='manager'?'selected':'' ?>>Manager</option>
            <option value="cashier" <?= $user['role']=='cashier'?'selected':'' ?>>Cashier</option>

        </select>
    </div>

    <div class="form-group" style="margin-bottom: 25px;">
        <label style="font-weight: 600; display: block; margin-bottom: 6px;">New Password</label>
        <input 
            type="password" 
            name="password" 
            placeholder="Leave blank to keep current password"
            class="form-control"
            style="padding: 10px; border-radius: 8px;">
    </div>

    <div style="text-align: right;">
        <a href="users.php" class="btn btn-secondary" style="margin-right: 10px;">
            Cancel
        </a>

        <button name="update_user" class="btn btn-success" style="padding: 10px 20px; border-radius: 8px;">
            Update User
        </button>
    </div>

</form>

</div>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>