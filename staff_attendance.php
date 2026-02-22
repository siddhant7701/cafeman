<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Set default date to today if not provided
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/update attendance
    if (isset($_POST['save_attendance'])) {
        $attendance_date = $_POST['attendance_date'];
        $staff_ids = $_POST['staff_id'];
        $statuses = $_POST['status'];
        $check_ins = $_POST['check_in'];
        $check_outs = $_POST['check_out'];
        $deductions = $_POST['deduction'];
        $notes = $_POST['notes'];
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete existing attendance records for this date
            $stmt = $pdo->prepare("DELETE FROM staff_attendance WHERE attendance_date = ?");
            $stmt->execute([$attendance_date]);
            
            // Insert new attendance records
            for ($i = 0; $i < count($staff_ids); $i++) {
                $stmt = $pdo->prepare("INSERT INTO staff_attendance (staff_id, attendance_date, status, check_in, check_out, deduction, notes, created_by) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $staff_ids[$i],
                    $attendance_date,
                    $statuses[$i],
                    !empty($check_ins[$i]) ? $check_ins[$i] : null,
                    !empty($check_outs[$i]) ? $check_outs[$i] : null,
                    $deductions[$i],
                    $notes[$i],
                    $_SESSION['user_id']
                ]);
            }
            
            $pdo->commit();
            setFlashMessage('success', 'Attendance saved successfully');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('error', 'Error saving attendance: ' . $e->getMessage());
        }
        
        header("Location: staff_attendance.php?date=$attendance_date");
        exit();
    }
}

// Get staff members
try {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $staffMembers = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching staff: ' . $e->getMessage());
    $staffMembers = [];
}

// Get attendance for selected date
try {
    $stmt = $pdo->prepare("SELECT * FROM staff_attendance WHERE attendance_date = ?");
    $stmt->execute([$selected_date]);
    $attendanceRecords = $stmt->fetchAll();

    // Create attendance map for easy access
    $attendanceMap = [];
    foreach ($attendanceRecords as $record) {
        $attendanceMap[$record['staff_id']] = $record;
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching attendance: ' . $e->getMessage());
    $attendanceMap = [];
}

// Include header
include_once 'includes/header.php';
?>

<h1 class="page-title">Staff Attendance</h1>

<div class="row">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Attendance for <?php echo date('F d, Y', strtotime($selected_date)); ?></h2>
                <div>
                    <form action="staff_attendance.php" method="get" class="form-inline">
                        <input style="width:200px;" type="date" name="date" class="form-control datepicker" value="<?php echo $selected_date; ?>">
                        <button type="submit" style=" color: white; border: 1px solid #ffffff; padding: 5px 10px; border-radius: 3px;" class="btn">Go</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="staff_attendance.php" method="post">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Status</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Deduction</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staffMembers)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No staff members found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($staffMembers as $staff): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($staff['name']); ?>
                                        <input type="hidden" name="staff_id[]" value="<?php echo $staff['id']; ?>">
                                    </td>
                                    <td>
                                        <select name="status[]" class="form-control">
                                            <option value="present" <?php echo isset($attendanceMap[$staff['id']]) && $attendanceMap[$staff['id']]['status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo isset($attendanceMap[$staff['id']]) && $attendanceMap[$staff['id']]['status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="late" <?php echo isset($attendanceMap[$staff['id']]) && $attendanceMap[$staff['id']]['status'] == 'late' ? 'selected' : ''; ?>>Late</option>
                                            <option value="half-day" <?php echo isset($attendanceMap[$staff['id']]) && $attendanceMap[$staff['id']]['status'] == 'half-day' ? 'selected' : ''; ?>>Half Day</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" name="check_in[]" class="form-control" value="<?php echo isset($attendanceMap[$staff['id']]) ? $attendanceMap[$staff['id']]['check_in'] : ''; ?>">
                                    </td>
                                    <td>
                                        <input type="time" name="check_out[]" class="form-control" value="<?php echo isset($attendanceMap[$staff['id']]) ? $attendanceMap[$staff['id']]['check_out'] : ''; ?>">
                                    </td>
                                    <td>
                                        <input type="number" name="deduction[]" class="form-control" step="0.01" min="0" value="<?php echo isset($attendanceMap[$staff['id']]) ? $attendanceMap[$staff['id']]['deduction'] : '0'; ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="notes[]" class="form-control" value="<?php echo isset($attendanceMap[$staff['id']]) ? htmlspecialchars($attendanceMap[$staff['id']]['notes']) : ''; ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="form-group">
                    <button type="submit" name="save_attendance" class="btn btn-success">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Salary Report Section -->
<div class="row mt-4">
    <div class="card">
        
        <div class="card-header">
            <h2>Salary Report</h2>
        </div>
        <div class="card-body">
            <form action="staff_salary_report.php" method="get" class="form-inline">
                <div class="form-group mr-2">
                    <label for="month" class="mr-2">Month:</label>
                    <input type="month" id="month" name="month" class="form-control" value="<?php echo date('Y-m'); ?>">
                </div>
                <button type="submit" class="btn">Generate Report</button>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize datepicker
document.addEventListener('DOMContentLoaded', function() {
    // Set default date to today if not already set
    if (!document.querySelector('.datepicker').value) {
        document.querySelector('.datepicker').valueAsDate = new Date();
    }
});
</script>

<?php include_once 'includes/footer.php'; 
$current_page = basename($_SERVER['PHP_SELF']);
?>