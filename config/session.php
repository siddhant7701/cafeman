<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user has admin role
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Function to check if user has manager role
function isManager() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'manager' || $_SESSION['user_role'] == 'admin');
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "You must be logged in to access this page";
        header("Location: index.php");
        exit();
    }
}

// Function to redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = "You don't have permission to access this page";
        header("Location: dashboard.php");
        exit();
    }
}

// Function to redirect if not manager or admin
function requireManager() {
    requireLogin();
    if (!isManager()) {
        $_SESSION['error'] = "You don't have permission to access this page";
        header("Location: dashboard.php");
        exit();
    }
}

// Function to set flash message
function setFlashMessage($type, $message) {
    $_SESSION[$type] = $message;
}

// Function to display flash message and clear it
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
?>
