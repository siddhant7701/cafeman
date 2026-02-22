<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// Fetch staff
$stmt = $pdo->prepare("SELECT * FROM staff WHERE status = 'active' ORDER BY name");
$stmt->execute();
$staffMembers = $stmt->fetchAll();

// Fetch attendance
$stmt = $pdo->prepare("SELECT * FROM staff_attendance 
    WHERE attendance_date BETWEEN ? AND ? 
    ORDER BY staff_id, attendance_date");
$stmt->execute([$month_start, $month_end]);
$attendanceRecords = $stmt->fetchAll();

$attendanceByStaff = [];
foreach ($attendanceRecords as $record) {
    $attendanceByStaff[$record['staff_id']][] = $record;
}

// Excel headers
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=salary_report_$selected_month.xls");

echo "Staff\tPosition\tDaily Rate\tPresent\tAbsent\tLate\tHalf Days\tWorking Days\tBase Salary\tDeduction\tNet Salary\n";

foreach ($staffMembers as $staff) {

    $present = $absent = $late = $half = $deduction = 0;
    $dailyRate = $staff['daily_rate'];

    if (isset($attendanceByStaff[$staff['id']])) {
        foreach ($attendanceByStaff[$staff['id']] as $record) {
            switch ($record['status']) {
                case 'present': $present++; break;
                case 'absent': $absent++; break;
                case 'late': $late++; break;
                case 'half-day': $half++; break;
            }
            $deduction += $record['deduction'];
        }
    }

    $workingDays = $present + $late + ($half * 0.5);
    $baseSalary = $workingDays * $dailyRate;
    $netSalary = $baseSalary - $deduction;

    echo "{$staff['name']}\t{$staff['position']}\t{$dailyRate}\t$present\t$absent\t$late\t$half\t$workingDays\t$baseSalary\t$deduction\t$netSalary\n";
}

exit;