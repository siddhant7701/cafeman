// Toggle mobile menu
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
    
    // Initialize datepickers
    const datepickers = document.querySelectorAll('.datepicker');
    if (datepickers.length > 0) {
        datepickers.forEach(function(picker) {
            flatpickr(picker, {
                dateFormat: "Y-m-d"
            });
        });
    }
    
    // Initialize select2 dropdowns
    const select2Elements = document.querySelectorAll('.select2');
    if (select2Elements.length > 0 && typeof $.fn.select2 !== 'undefined') {
        $(select2Elements).select2();
    }
    
    // Alert auto-close
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    }
    
    // Billing page functionality
    initializeBilling();
    
    // Chart initialization for dashboard
    initializeCharts();
});

// Billing page functionality
function initializeBilling() {
    const menuItems = document.querySelectorAll('.menu-item');
    const billItemsContainer = document.querySelector('.bill-items-container');
    const subtotalElement = document.getElementById('subtotal');
    const taxElement = document.getElementById('tax-amount');
    const totalElement = document.getElementById('total-amount');
    const categoryButtons = document.querySelectorAll('.category-btn');
    
    if (!menuItems.length) return;
    
    // Category filter
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Update active button
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter menu items
            menuItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Add menu item to bill
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            
            // Check if item already exists in bill
            const existingItem = document.querySelector(`.bill-item[data-id="${id}"]`);
            
            if (existingItem) {
                // Update quantity
                const quantityInput = existingItem.querySelector('.item-quantity');
                const currentQuantity = parseInt(quantityInput.value);
                quantityInput.value = currentQuantity + 1;
                
                // Update price
                const itemTotal = existingItem.querySelector('.item-total');
                itemTotal.textContent = (price * (currentQuantity + 1)).toFixed(2);
            } else {
                // Create new bill item
                const billItem = document.createElement('div');
                billItem.classList.add('bill-item');
                billItem.dataset.id = id;
                billItem.dataset.price = price;
                
                billItem.innerHTML = `
                    <div class="item-name">${name}</div>
                    <div class="item-price">${price.toFixed(2)}</div>
                    <input type="number" class="item-quantity" value="1" min="1">
                    <div class="item-total">${price.toFixed(2)}</div>
                    <button class="btn-remove">×</button>
                    <input type="hidden" name="item_id[]" value="${id}">
                    <input type="hidden" name="item_price[]" value="${price}">
                    <input type="hidden" name="item_quantity[]" value="1">
                `;
                
                billItemsContainer.appendChild(billItem);
                
                // Add event listener to quantity input
                const quantityInput = billItem.querySelector('.item-quantity');
                quantityInput.addEventListener('change', updateBillTotals);
                
                // Add event listener to remove button
                const removeButton = billItem.querySelector('.btn-remove');
                removeButton.addEventListener('click', function() {
                    billItem.remove();
                    updateBillTotals();
                });
            }
            
            updateBillTotals();
        });
    });
    
    // Update bill totals
    function updateBillTotals() {
        const billItems = document.querySelectorAll('.bill-item');
        let subtotal = 0;
        
        billItems.forEach(item => {
            const price = parseFloat(item.dataset.price);
            const quantity = parseInt(item.querySelector('.item-quantity').value);
            const itemTotal = price * quantity;
            
            item.querySelector('.item-total').textContent = itemTotal.toFixed(2);
            item.querySelector('input[name="item_quantity[]"]').value = quantity;
            
            subtotal += itemTotal;
        });
        
        const taxRate = 0.05; // 5% tax
        const tax = subtotal * taxRate;
        const total = subtotal + tax;
        
        subtotalElement.textContent = subtotal.toFixed(2);
        taxElement.textContent = tax.toFixed(2);
        totalElement.textContent = total.toFixed(2);
        
        document.getElementById('subtotal-input').value = subtotal.toFixed(2);
        document.getElementById('tax-input').value = tax.toFixed(2);
        document.getElementById('total-input').value = total.toFixed(2);
    }
}

// Initialize charts for dashboard
function initializeCharts() {
    const salesChart = document.getElementById('salesChart');
    const inventoryChart = document.getElementById('inventoryChart');
    
    if (salesChart && typeof Chart !== 'undefined') {
        new Chart(salesChart, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Sales',
                    data: [12, 19, 3, 5, 2, 3, 7],
                    borderColor: '#5c4033',
                    backgroundColor: 'rgba(92, 64, 51, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    if (inventoryChart && typeof Chart !== 'undefined') {
        new Chart(inventoryChart, {
            type: 'bar',
            data: {
                labels: ['Coffee', 'Milk', 'Sugar', 'Tea', 'Flour'],
                datasets: [{
                    label: 'Stock Level',
                    data: [12, 19, 3, 5, 2],
                    backgroundColor: [
                        'rgba(92, 64, 51, 0.7)',
                        'rgba(139, 90, 43, 0.7)',
                        'rgba(210, 180, 140, 0.7)',
                        'rgba(245, 245, 220, 0.7)',
                        'rgba(60, 36, 21, 0.7)'
                    ],
                    borderColor: [
                        'rgba(92, 64, 51, 1)',
                        'rgba(139, 90, 43, 1)',
                        'rgba(210, 180, 140, 1)',
                        'rgba(245, 245, 220, 1)',
                        'rgba(60, 36, 21, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// Print bill function
function printBill() {
    const printWindow = window.open('', '_blank');
    const billContent = document.getElementById('bill-content').innerHTML;
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Bill Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .bill-header { text-align: center; margin-bottom: 20px; }
                .bill-items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .bill-items th, .bill-items td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .bill-total { margin-top: 20px; text-align: right; }
            </style>
        </head>
        <body>
            <div class="bill-header">
                <h2>CafeMan</h2>
                <p>Receipt #${Math.floor(Math.random() * 10000)}</p>
                <p>${new Date().toLocaleString()}</p>
            </div>
            ${billContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

// Confirm delete
function confirmDelete(message, formId) {
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
    return false;
}