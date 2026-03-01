<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

/* =========================
   HANDLE FORM SUBMISSIONS
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD STAFF
    if (isset($_POST['add_staff'])) {

        $name       = trim($_POST['name']);
        $phone      = trim($_POST['phone']);
        $position   = trim($_POST['position']);
        $daily_rate = floatval($_POST['daily_rate']);
        $join_date  = $_POST['join_date'];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO staff (name, phone, position, daily_rate, join_date, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$name, $phone, $position, $daily_rate, $join_date]);
            setFlashMessage('success', 'Staff added successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding staff');
        }

        header('Location: staff.php');
        exit();
    }

    // UPDATE STAFF
    if (isset($_POST['update_staff'])) {

        $staff_id   = intval($_POST['staff_id']);
        $name       = trim($_POST['name']);
        $phone      = trim($_POST['phone']);
        $position   = trim($_POST['position']);
        $daily_rate = floatval($_POST['daily_rate']);
        $status     = $_POST['status'];

        try {
            $stmt = $pdo->prepare("
                UPDATE staff 
                SET name=?, phone=?, position=?, daily_rate=?, status=?
                WHERE id=?
            ");
            $stmt->execute([$name, $phone, $position, $daily_rate, $status, $staff_id]);
            setFlashMessage('success', 'Staff updated successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating staff');
        }

        header('Location: staff.php');
        exit();
    }

    // DELETE STAFF
    if (isset($_POST['delete_staff'])) {

        $staff_id = intval($_POST['staff_id']);

        try {
            $stmt = $pdo->prepare("DELETE FROM staff WHERE id=?");
            $stmt->execute([$staff_id]);
            setFlashMessage('success', 'Staff deleted successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error deleting staff');
        }

        header('Location: staff.php');
        exit();
    }
}

/* =========================
   FETCH STAFF
========================= */

try {
    $stmt = $pdo->query("SELECT * FROM staff ORDER BY name ASC");
    $staffMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $staffMembers = [];
}

include_once 'includes/header.php';
?>

<style>
.custom-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(4px);
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-box {
    background: #ffffff;
    width: 420px;
    max-width: 95%;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    animation: modalSlide 0.3s ease;
}

@keyframes modalSlide {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.close-btn {
    font-size: 22px;
    cursor: pointer;
    color: #777;
}

.close-btn:hover {
    color: #000;
}

.modal-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.modal-form input,
.modal-form select {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.modal-form input:focus,
.modal-form select:focus {
    outline: none;
    border-color: #4CAF50;
}

.primary-btn {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
}

.primary-btn:hover {
    background: #45a049;
}
</style>

<h1 class="page-title">Staff Management</h1>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Staff Members</span>
        <button type="button" class="btn" onclick="openModal('addStaffModal')">
            + Add Staff
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
                            <td><?= htmlspecialchars($staff['name']) ?></td>
                            <td><?= htmlspecialchars($staff['phone']) ?></td>
                            <td><?= htmlspecialchars($staff['position']) ?></td>
                            <td><?= number_format($staff['daily_rate'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($staff['join_date'])) ?></td>
                            <td><?= ucfirst($staff['status']) ?></td>
                            <td>
                                <button class="btn btn-info btn-sm"
                                    onclick='editStaff(<?= json_encode($staff) ?>)'>Edit</button>

                                <button class="btn btn-danger btn-sm"
                                    onclick="deleteStaff(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['name'], ENT_QUOTES) ?>')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD MODAL -->
<div class="custom-modal" id="addStaffModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add Staff</h3>
            <span class="close-btn" onclick="closeModal('addStaffModal')">&times;</span>
        </div>

        <form method="post" class="modal-form">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="phone" placeholder="Phone">
            <input type="text" name="position" placeholder="Position" required>
            <input type="number" name="daily_rate" step="0.01" min="0" placeholder="Daily Rate" required>
            <input type="date" name="join_date" value="<?= date('Y-m-d') ?>" required>

            <button type="submit" name="add_staff" class="primary-btn">
                Add Staff
            </button>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="custom-modal" id="editStaffModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Edit Staff</h3>
            <span class="close-btn" onclick="closeModal('editStaffModal')">&times;</span>
        </div>

        <form method="post" class="modal-form">
            <input type="hidden" name="staff_id" id="edit_staff_id">
            <input type="text" name="name" id="edit_name" required>
            <input type="text" name="phone" id="edit_phone">
            <input type="text" name="position" id="edit_position" required>
            <input type="number" name="daily_rate" id="edit_daily_rate" step="0.01" min="0" required>

            <select name="status" id="edit_status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <button type="submit" name="update_staff" class="primary-btn">
                Update Staff
            </button>
        </form>
    </div>
</div>

<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="staff_id" id="delete_id">
    <input type="hidden" name="delete_staff" value="1">
</form>

<script>
function openModal(id){
    document.getElementById(id).style.display = "flex";
}

function closeModal(id){
    document.getElementById(id).style.display = "none";
}

function editStaff(staff){
    document.getElementById('edit_staff_id').value = staff.id;
    document.getElementById('edit_name').value = staff.name;
    document.getElementById('edit_phone').value = staff.phone;
    document.getElementById('edit_position').value = staff.position;
    document.getElementById('edit_daily_rate').value = staff.daily_rate;
    document.getElementById('edit_status').value = staff.status;
    openModal('editStaffModal');
}

function deleteStaff(id, name){
    if(confirm("Delete " + name + "?")){
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

window.onclick = function(e){
    if(e.target.classList.contains("custom-modal")){
        e.target.style.display = "none";
    }
}
</script>

<?php include_once 'includes/footer.php'; ?>