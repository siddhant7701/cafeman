<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Fetch system settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();

include_once 'includes/header.php';
?>

<h1 class="page-title">Swiggy Orders</h1>

<?php if (empty($settings['swiggy_api_key'])): ?>

<div class="card p-4" style="border-left:5px solid red;padding:20px;">
    <h3 style="color:red;">⚠ Swiggy API Not Connected</h3>
    <p>Please add Swiggy API Key in Settings to start receiving orders.</p>
    <a href="settings.php" class="btn btn-primary">Go to Settings</a>
</div>

<?php else: ?>

<div class="card p-4" style="padding:20px;">

<h3>Live Swiggy Orders</h3>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody id="swiggy-orders-body">
        <tr>
            <td colspan="5" class="text-center">Loading orders...</td>
        </tr>
    </tbody>
</table>

</div>

<script>
function fetchSwiggyOrders(){
    fetch('fetch_orders.php?platform=swiggy')
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById('swiggy-orders-body');
        tbody.innerHTML = '';

        if(data.length === 0){
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">
                        No Swiggy Orders Yet
                    </td>
                </tr>
            `;
            return;
        }

        data.forEach(order => {

            let statusColor = 'gray';

            if(order.status === 'confirmed') statusColor = 'green';
            if(order.status === 'cancelled') statusColor = 'red';
            if(order.status === 'pending') statusColor = 'orange';

            tbody.innerHTML += `
                <tr>
                    <td>${order.external_order_id ?? '-'}</td>
                    <td>${order.customer_name ?? '-'}</td>
                    <td>₹ ${order.amount ?? '0.00'}</td>
                    <td style="color:${statusColor}; font-weight:bold;">
                        ${order.status ?? '-'}
                    </td>
                    <td>${order.created_at}</td>
                </tr>
            `;
        });

    })
    .catch(error => {
        console.error("Error fetching Swiggy orders:", error);
    });
}

// Initial load
fetchSwiggyOrders();

// Auto refresh every 5 seconds
setInterval(fetchSwiggyOrders, 5000);
</script>

<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>