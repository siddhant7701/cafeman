<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new staff
    if (isset($_POST['add_staff'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $position = trim($_POST['position']);
        $daily_rate = $_POST['daily_rate'];
        $join_date = $_POST['join_date'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO staff (name, phone, position, daily_rate, join_date) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $position, $daily_rate, $join_date]);
            setFlashMessage('success', 'Staff added successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding staff: ' . $e->getMessage());
        }
        
        header('Location: staff.php');
        exit();
    }
    
    // Update staff
    if (isset($_POST['update_staff'])) {
        $staff_id = $_POST['staff_id'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $position = trim($_POST['position']);
        $daily_rate = $_POST['daily_rate'];
        $status = $_POST['status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE staff SET name = ?, phone = ?, position = ?, daily_rate = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $position, $daily_rate, $status, $staff_id]);
            setFlashMessage('success', 'Staff updated successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating staff: ' . $e->getMessage());
        }
        
        header('Location: staff.php');
        exit();
    }
    
    // Delete staff
    if (isset($_POST['delete_staff'])) {
        $staff_id = $_POST['staff_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
            $stmt->execute([$staff_id]);
            setFlashMessage('success', 'Staff deleted successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error deleting staff: ' . $e->getMessage());
        }
        
        header('Location: staff.php');
        exit();
    }
}

// Get staff
try {
    $stmt = $pdo->prepare("SELECT * FROM staff ORDER BY name");
    $stmt->execute();
    $staffMembers = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching staff: ' . $e->getMessage());
    $staffMembers = [];
}

// Include header
include_once 'includes/header.php';
?>

<h1 class="page-title">Staff Management</h1>

<div class="row">
    <div class="card">
        <div class="card-header">
            Staff Members
            <button type="button" class="btn" onclick="openModal('addStaffModal')">
                <i class="fas fa-plus"></i> Add Staff
            </button>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Daily Rate</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staffMembers)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No staff members found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($staffMembers as $staff): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                <td><?php echo htmlspecialchars($staff['position']); ?></td>
                                <td><?php echo number_format($staff['daily_rate'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($staff['join_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $staff['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($staff['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" onclick='editStaff(<?php echo json_encode($staff); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal" id="addStaffModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Staff</h5>
                <button type="button" class="close" onclick="closeModal('addStaffModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="staff.php" method="post">
                    <div class="form-group">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" id="position" name="position" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="daily_rate" class="form-label">Daily Rate</label>
                        <input type="number" id="daily_rate" name="daily_rate" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="join_date" class="form-label">Join Date</label>
                        <input type="date" id="join_date" name="join_date" class="form-control datepicker" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_staff" class="btn btn-block">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal" id="editStaffModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Staff</h5>
                <button type="button" class="close" onclick="closeModal('editStaffModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="staff.php" method="post">
                    <input type="hidden" id="edit_staff_id" name="staff_id">
                    
                    <div class="form-group">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_position" class="form-label">Position</label>
                        <input type="text" id="edit_position" name="position" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_daily_rate" class="form-label">Daily Rate</label>
                        <input type="number" id="edit_daily_rate" name="daily_rate" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status" class="form-label">Status</label>
                        <select id="edit_status" name="status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_staff" class="btn btn-block">Update Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Staff Form -->
<form id="deleteStaffForm" action="staff.php" method="post" style="display: none;">
    <input type="hidden" id="delete_staff_id" name="staff_id">
    <input type="hidden" name="delete_staff" value="1">
</form>

<script>
// Function to open modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

// Function to close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Edit staff
function editStaff(staff) {
    document.getElementById('edit_staff_id').value = staff.id;
    document.getElementById('edit_name').value = staff.name;
    document.getElementById('edit_phone').value = staff.phone;
    document.getElementById('edit_position').value = staff.position;
    document.getElementById('edit_daily_rate').value = staff.daily_rate;
    document.getElementById('edit_status').value = staff.status;
    
    // Show modal
    openModal('editStaffModal');
}

// Delete staff
function deleteStaff(staffId, staffName) {
    if (confirm(`Are you sure you want to delete ${staffName}?`)) {
        document.getElementById('delete_staff_id').value = staffId;
        document.getElementById('deleteStaffForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};

// Initialize datepicker
document.addEventListener('DOMContentLoaded', function() {
    // Set default date to today
    document.getElementById('join_date').valueAsDate = new Date();
});
</script>

<?php include_once 'includes/footer.php'; ?>