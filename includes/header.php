<?php
require_once 'config/session.php';
require_once 'config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'index.php') {
    requireLogin();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CafeMan - Cafe Management System</title>

<!-- Favicon -->
<link rel="icon" type="image/png" href="uploads/logo.png">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>

/* Keep original theme color */
.header {
    background:#08630C;
}

/* Logo */
.logo img {
    height:60px;
    width: 70px;
}

/* Navigation */
.nav-menu {
    list-style:none;
    display:flex;
    gap:20px;
    align-items:center;
}

.nav-link {
    text-decoration:none;
    padding:8px 12px;
    border-radius:6px;
    color:white;
    transition:0.3s;
}

.nav-link:hover {
    background:rgba(4, 58, 9, 0.15);
}

.nav-link.active {
    background:rgba(4, 58, 9, 0.66);
}

/* Dropdown */
.dropdown {
    position:relative;
}

.dropdown-menu {
    display:none;
    position:absolute;
    top:30px;
    right:0;
    background:white;
    min-width:180px;
    border-radius:8px;
    box-shadow:0 6px 18px #08630daf;
    overflow:hidden;
    z-index:999;
}

.dropdown-menu a {
    padding:10px 15px;
    display:block;
    text-decoration:none;
    color:#333;
}

.dropdown-menu a:hover {
    background:#f4f4f4;
}

.dropdown:hover .dropdown-menu {
    display:block;
}

/* User avatar */
.user-avatar {
    width:38px;
    height:38px;
    border-radius:50%;
    cursor:pointer;
}
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4);
}

.modal-dialog {
    margin: 8% auto;
    width: 400px;
}

.modal-content {
    background: #fff;
    border-radius: 10px;
    padding: 15px;
}
</style>

</head>

<body>

<?php if (isLoggedIn() && $current_page != 'index.php'): ?>

<header class="header">
<div class="container" style="display:flex;justify-content:space-between;align-items:center;">

<!-- Logo -->
<a href="dashboard.php" class="logo">
<img src="uploads/logo.png" alt="Logo">
</a>

<!-- Navigation -->
<ul class="nav-menu">

<li>
<a href="dashboard.php"
class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>">
Dashboard
</a>
</li>

<li>
<a href="billing.php"
class="nav-link <?= $current_page=='billing.php'?'active':'' ?>">
Billing
</a>
</li>

<?php if (isManager()): ?>

<li>
<a href="inventory.php"
class="nav-link <?= $current_page=='inventory.php'?'active':'' ?>">
Inventory
</a>
</li>

<li>
<a href="menu.php"
class="nav-link <?= $current_page=='menu.php'?'active':'' ?>">
Menu
</a>
</li>

<li class="dropdown">
<a href="#" class="nav-link">Staff ▾</a>
<div class="dropdown-menu" style="left:0;right:auto;">
<a href="staff.php">Staff List</a>
<a href="staff_attendance.php">Attendance</a>
<a href="staff_salary_report.php">Salary Report</a>
</div>
</li>

<?php endif; ?>

<li>
<a href="reservations.php"
class="nav-link <?= $current_page=='reservations.php'?'active':'' ?>">
Reservations
</a>
</li>

<li class="dropdown">
<a href="#" class="nav-link">Online Orders ▾</a>
<div class="dropdown-menu" style="left:0;right:auto;">
<a href="zomato_orders.php">Zomato Orders</a>
<a href="swiggy_orders.php">Swiggy Orders</a>
</div>
</li>

</ul>

<!-- USER DROPDOWN -->
<div class="dropdown">

<img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name']) ?>&background=ffffff&color=5c4033"
class="user-avatar">

<div class="dropdown-menu">

<div style="padding:10px;font-weight:bold;border-bottom:1px solid #eee;background:rgba(4, 58, 9, 0.92);
">
<?= $_SESSION['user_name'] ?>
</div>

<?php if (isAdmin()): ?>
<a href="users.php">
<i class="fas fa-users"></i> Users
</a>
<?php endif; ?>

<?php if (isAdmin()): ?>
<a href="settings.php">
<i class="fas fa-cog"></i> Settings
</a>
<?php endif; ?>

<a href="logout.php" style="color:red;">
<i class="fas fa-sign-out-alt"></i> Logout
</a>

</div>

</div>

</div>
</header>

<?php endif; ?>

<main class="main">
<div class="container">
<?= displayFlashMessage(); ?>

<!-- ORDER POPUP -->
<div id="orderPopup" style="
display:none;
position:fixed;
bottom:20px;
right:20px;
background:#28a745;
color:white;
padding:20px;
border-radius:10px;
box-shadow:0 0 10px rgba(0,0,0,0.3);
z-index:9999;">
New Order Received!
</div>

<script>
let lastOrderId=0;

function checkNewOrders(){
fetch('fetch_latest_order.php')
.then(res=>res.json())
.then(order=>{
if(order && order.id>lastOrderId){
lastOrderId=order.id;
let popup=document.getElementById('orderPopup');
popup.innerHTML="New "+order.platform+" Order from "+order.customer_name;
popup.style.display="block";
setTimeout(()=>popup.style.display="none",5000);
}
});
}

setInterval(checkNewOrders,5000);
document.addEventListener('DOMContentLoaded', function() {

    flatpickr("#join_date", {
        dateFormat: "Y-m-d",
        defaultDate: "today",
        maxDate: "today"
    });

});
</script>