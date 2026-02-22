<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Set default month to current month if not provided
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// Get staff members
$stmt = $pdo->prepare("SELECT * FROM staff WHERE status = 'active' ORDER BY name");
$stmt->execute();
$staffMembers = $stmt->fetchAll();

// Get attendance for selected month
$stmt = $pdo->prepare("SELECT * FROM staff_attendance WHERE attendance_date BETWEEN ? AND ? ORDER BY staff_id, attendance_date");
$stmt->execute([$month_start, $month_end]);
$attendanceRecords = $stmt->fetchAll();

// Group attendance by staff
$attendanceByStaff = [];
foreach ($attendanceRecords as $record) {
    if (!isset($attendanceByStaff[$record['staff_id']])) {
        $attendanceByStaff[$record['staff_id']] = [];
    }
    $attendanceByStaff[$record['staff_id']][] = $record;
}

// Calculate salary for each staff
$salaryData = [];
foreach ($staffMembers as $staff) {
    $staffId = $staff['id'];
    $dailyRate = $staff['daily_rate'];
    
    // Initialize counters
    $presentDays = 0;
    $absentDays = 0;
    $lateDays = 0;
    $halfDays = 0;
    $totalDeduction = 0;
    
    // Count attendance
    if (isset($attendanceByStaff[$staffId])) {
        foreach ($attendanceByStaff[$staffId] as $record) {
            switch ($record['status']) {
                case 'present':
                    $presentDays++;
                    break;
                case 'absent':
                    $absentDays++;
                    break;
                case 'late':
                    $lateDays++;
                    break;
                case 'half-day':
                    $halfDays++;
                    break;
            }
            $totalDeduction += $record['deduction'];
        }
    }
    
    // Calculate salary
    $workingDays = $presentDays + $lateDays + ($halfDays * 0.5);
    $baseSalary = $workingDays * $dailyRate;
    $netSalary = $baseSalary - $totalDeduction;
    
    // Store salary data
    $salaryData[$staffId] = [
        'staff' => $staff,
        'present_days' => $presentDays,
        'absent_days' => $absentDays,
        'late_days' => $lateDays,
        'half_days' => $halfDays,
        'working_days' => $workingDays,
        'base_salary' => $baseSalary,
        'deduction' => $totalDeduction,
        'net_salary' => $netSalary
    ];
}

// Include header
include_once 'includes/header.php';
?>

<h1 class="page-title">Staff Salary Report</h1>

<div class="row">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Salary Report for <?php echo date('F Y', strtotime($month_start)); ?></h2>
                <div>
                    <form action="staff_salary_report.php" method="get" class="form-inline">
                        <input type="month" name="month" class="form-control" value="<?php echo $selected_month; ?>">
                        <button type="submit" class="btn">Go</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Position</th>
                        <th>Daily Rate</th>
                        <th>Present Days</th>
                        <th>Absent Days</th>
                        <th>Late Days</th>
                        <th>Half Days</th>
                        <th>Working Days</th>
                        <th>Base Salary</th>
                        <th>Deduction</th>
                        <th>Net Salary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staffMembers)): ?>
                        <tr>
                            <td colspan="11" class="text-center">No staff members found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($staffMembers as $staff): ?>
                            <?php $data = $salaryData[$staff['id']] ?? null; ?>
                            <?php if ($data): ?>
                                <tr>
                                    <td><?php echo $staff['name']; ?></td>
                                    <td><?php echo $staff['position']; ?></td>
                                    <td><?php echo number_format($staff['daily_rate'], 2); ?></td>
                                    <td><?php echo $data['present_days']; ?></td>
                                    <td><?php echo $data['absent_days']; ?></td>
                                    <td><?php echo $data['late_days']; ?></td>
                                    <td><?php echo $data['half_days']; ?></td>
                                    <td><?php echo $data['working_days']; ?></td>
                                    <td><?php echo number_format($data['base_salary'], 2); ?></td>
                                    <td><?php echo number_format($data['deduction'], 2); ?></td>
                                    <td><?php echo number_format($data['net_salary'], 2); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><?php echo $staff['name']; ?></td>
                                    <td><?php echo $staff['position']; ?></td>
                                    <td><?php echo number_format($staff['daily_rate'], 2); ?></td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td>0.00</td>
                                    <td>0.00</td>
                                    <td>0.00</td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="text-right mt-3">
                <button onclick="printReport()" class="btn btn-info">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {
    const printWindow = window.open('', '_blank');
    const reportTitle = document.querySelector('.card-header h2').textContent;
    const reportContent = document.querySelector('.card-body').innerHTML;
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Salary Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .text-right { text-align: right; }
            </style>
        </head>
        <body>
            <h1>${reportTitle}</h1>
            ${reportContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}
</script>

<?php include_once 'includes/footer.php'; ?>