<?php
require_once 'config/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Flash message functions
function setFlashMessage($type, $message) {
    $_SESSION[$type] = $message;
}

function displayFlashMessage() {
    $types = ['success', 'error', 'warning', 'info'];
    $output = '';
    
    foreach ($types as $type) {
        if (isset($_SESSION[$type])) {
            $alertClass = $type == 'error' ? 'danger' : $type;
            $output .= "<div class='alert alert-{$alertClass}'>{$_SESSION[$type]}</div>";
            unset($_SESSION[$type]);
        }
    }
    
    return $output;
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        setFlashMessage('error', 'Please enter both username and password');
    } else {

        // Fetch user from DB
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: dashboard.php');
            exit();

        } else {
            setFlashMessage('error', 'Invalid username or password');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CafeMan - Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 350px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-logo h1 span {
            color: #4CAF50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-logo">
        <h1>Cafe<span>Man</span></h1>
        <p>Cafe Management System</p>
    </div>

    <?php echo displayFlashMessage(); ?>

    <form method="post">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>
</div>

</body>
</html>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>