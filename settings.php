<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

$isAdmin = ($_SESSION['user_role'] === 'admin');

/* =========================
   HANDLE SYSTEM SETTINGS (ADMIN)
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($isAdmin && isset($_POST['update_system'])) {

        $site_name = trim($_POST['site_name']);
        $gst_percentage = $_POST['gst_percentage'];
        $razorpay_key = trim($_POST['razorpay_key']);
        $razorpay_secret = trim($_POST['razorpay_secret']);
        $zomato_api_key = trim($_POST['zomato_api_key']);
        $swiggy_api_key = trim($_POST['swiggy_api_key']);

        $stmt = $pdo->prepare("UPDATE settings SET 
            site_name = ?,
            gst_percentage = ?,
            razorpay_key = ?,
            razorpay_secret = ?,
            zomato_api_key = ?,
            swiggy_api_key = ?
            WHERE id = 1");

        $stmt->execute([
            $site_name,
            $gst_percentage,
            $razorpay_key,
            $razorpay_secret,
            $zomato_api_key,
            $swiggy_api_key
        ]);

        setFlashMessage('success', 'System settings updated successfully');
        header("Location: settings.php");
        exit();
    }

    /* PROFILE UPDATE */

    if (isset($_POST['update_profile'])) {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $_SESSION['user_id']
        ]);

        $_SESSION['user_name'] = $_POST['name'];

        setFlashMessage('success', 'Profile updated successfully');
        header("Location: settings.php");
        exit();
    }

    /* PASSWORD UPDATE */

    if (isset($_POST['update_password'])) {

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($_POST['current_password'], $user['password'])) {
            setFlashMessage('error', 'Current password incorrect');
            header("Location: settings.php");
            exit();
        }

        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            setFlashMessage('error', 'Passwords do not match');
            header("Location: settings.php");
            exit();
        }

        $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$hashed, $_SESSION['user_id']]);

        setFlashMessage('success', 'Password updated successfully');
        header("Location: settings.php");
        exit();
    }
}

/* =========================
   FETCH DATA
========================= */

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->query("SELECT * FROM settings WHERE id=1");
$settings = $stmt->fetch();

include_once 'includes/header.php';
?>

<h1 class="page-title">Settings</h1>

<div class="card p-4" style="padding:20px;">

<!-- ================= PROFILE ================= -->

<h2>Profile</h2>
<form method="POST">

<div class="form-group">
<label>Name</label>
<input type="text" name="name" class="form-control"
value="<?= htmlspecialchars($user['name']) ?>" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" class="form-control"
value="<?= htmlspecialchars($user['email']) ?>">
</div>

<button name="update_profile" class="btn btn-primary">
Update Profile
</button>
</br>
</form>
</br>   
<hr>

<!-- ================= PASSWORD ================= -->

<h2>Change Password</h2>

<form method="POST">

<input type="password" name="current_password"
class="form-control mb-2" placeholder="Current Password" required>

<input type="password" name="new_password"
class="form-control mb-2" placeholder="New Password" required>

<input type="password" name="confirm_password"
class="form-control mb-2" placeholder="Confirm Password" required></br>
</br>
<button name="update_password" class="btn btn-warning">
Change Password
</button>
</br>
</form>

</div>

<?php if ($isAdmin): ?>

<br>

<div class="card p-4" style="padding:20px;">

<h2>System Settings (Admin Only)</h2>

<form method="POST">

<div class="form-group">
<label>Site Name</label>
<input type="text" name="site_name" class="form-control"
value="<?= htmlspecialchars($settings['site_name']) ?>">
</div>

<div class="form-group">
<label>GST Percentage</label>
<input type="number" step="0.01" name="gst_percentage"
class="form-control"
value="<?= $settings['gst_percentage'] ?>">
</div>

<hr>

<h3>Razorpay Settings</h3>

<input type="text" name="razorpay_key"
class="form-control mb-2"
placeholder="Razorpay Key ID"
value="<?= htmlspecialchars($settings['razorpay_key']) ?>">

<input type="text" name="razorpay_secret"
class="form-control mb-2"
placeholder="Razorpay Secret"
value="<?= htmlspecialchars($settings['razorpay_secret']) ?>">

<?php if (!$settings['razorpay_key']): ?>
<span style="color:red;">⚠ Razorpay Not Connected</span>
<?php else: ?>
<span style="color:green;">✔ Razorpay Connected</span>
<?php endif; ?>

<hr>

<h3>Zomato API</h3>

<input type="text" name="zomato_api_key"
class="form-control mb-2"
placeholder="Zomato API Key"
value="<?= htmlspecialchars($settings['zomato_api_key']) ?>">

<?php if (!$settings['zomato_api_key']): ?>
<span style="color:red;">⚠ Zomato Not Connected</span>
<?php else: ?>
<span style="color:green;">✔ Zomato Connected</span>
<?php endif; ?>

<hr>

<h3>Swiggy API</h3>

<input type="text" name="swiggy_api_key"
class="form-control mb-2"
placeholder="Swiggy API Key"
value="<?= htmlspecialchars($settings['swiggy_api_key']) ?>">

<?php if (!$settings['swiggy_api_key']): ?>
<span style="color:red;">⚠ Swiggy Not Connected</span>
<?php else: ?>
<span style="color:green;">✔ Swiggy Connected</span>
<?php endif; ?>

<br><br>

<button name="update_system" class="btn btn-success">
Save System Settings
</button>

</form>

</div>

<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>