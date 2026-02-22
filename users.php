<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireAdmin(); // Only admin can manage users

// CREATE USER
if (isset($_POST['create_user'])) {

    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users 
        (username, password, name, role, status, created_at)
        VALUES (?, ?, ?, ?, 1, NOW())");

    $stmt->execute([$username, $password, $name, $role]);

    setFlashMessage('success', 'User Created Successfully');
    header("Location: users.php");
    exit();
}

// ACTIVATE / DEACTIVATE
if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = IF(status=1,0,1) WHERE id=?");
    $stmt->execute([$_GET['toggle']]);

    setFlashMessage('success', 'User Status Updated');
    header("Location: users.php");
    exit();
}

// DELETE USER (prevent self delete)
if (isset($_GET['delete'])) {

    if ($_GET['delete'] == $_SESSION['user_id']) {
        setFlashMessage('error', 'You cannot delete yourself');
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        setFlashMessage('success', 'User Deleted');
    }

    header("Location: users.php");
    exit();
}

// FETCH USERS
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

include_once 'includes/header.php';
?>

<h1 class="page-title">User Management</h1>

<div class="card p-4 mb-4" style="
    padding: 20px;">
<h3>Create New User</h3>

<form method="POST" class="grid-2">

<div class="form-group">
<label>Username</label>
<input type="text" name="username" class="form-control" required>
</div>

<div class="form-group">
<label>Full Name</label>
<input type="text" name="name" class="form-control" required>
</div>

<div class="form-group">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="form-group">
<label>Role</label>
<select name="role" class="form-control">
<option value="admin">Admin</option>
<option value="manager">Manager</option>
<option value="cashier">Cashier</option>
</select>
</div>

<button type="submit" name="create_user" class="btn btn-success">
Create User
</button>

</form>
</div>

<div class="card p-4" style="
    padding: 20px;">

<h3>All Users</h3>

<table class="table table-striped">
<thead>
<tr>
<th>Name</th>
<th>Username</th>
<th>Role</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach($users as $u): ?>
<tr>
<td><?= htmlspecialchars($u['name']) ?></td>
<td><?= htmlspecialchars($u['username']) ?></td>
<td><?= ucfirst($u['role']) ?></td>
<td>
<?= $u['status'] ? 'Active' : 'Inactive' ?>
</td>
<td>

<a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-primary">
Edit
</a>

<a href="users.php?toggle=<?= $u['id'] ?>" class="btn btn-sm btn-warning">
<?= $u['status'] ? 'Deactivate' : 'Activate' ?>
</a>

<a href="users.php?delete=<?= $u['id'] ?>" 
onclick="return confirm('Delete this user?')" 
class="btn btn-sm btn-danger">
Delete
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