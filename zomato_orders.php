<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

$stmt = $pdo->query("SELECT * FROM settings WHERE id=1");
$settings = $stmt->fetch();

include_once 'includes/header.php';
?>

<h1 class="page-title">Zomato Orders</h1>

<?php if (empty($settings['zomato_api_key'])): ?>

<div class="card p-4" style="border-left:5px solid red;padding:20px;">
    <h3 style="color:red;">⚠ Zomato API Not Connected</h3>
    <p>Please add Zomato API key in Settings.</p>
    <a href="settings.php" class="btn btn-primary">Go to Settings</a>
</div>

<?php else: ?>

<div class="card p-4" style="padding:20px;">

<h3>Live Zomato Orders</h3>

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
    <tbody id="zomato-orders-body">
        <tr>
            <td colspan="5" class="text-center">
                Loading orders...
            </td>
        </tr>
    </tbody>
</table>

</div>

<script>
function fetchZomatoOrders(){

    fetch('fetch_orders.php?platform=zomato')
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById('zomato-orders-body');
        tbody.innerHTML = '';

        if(data.length === 0){
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">
                        No Zomato Orders Yet
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
        console.error("Error fetching Zomato orders:", error);
    });
}

// Initial load
fetchZomatoOrders();

// Auto refresh every 5 seconds
setInterval(fetchZomatoOrders, 5000);
</script>

<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>