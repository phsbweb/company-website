<?php
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../../user/attendance/db_connect.php';

// Get Parameters
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

if (!$employee_id) {
    header('Location: ../employees/employees.php');
    exit;
}

// Fetch Employee Details
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: ../employees/employees.php');
    exit;
}

// Fetch All Employees for dropdown
$all_employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")->fetchAll();

// Generate Month Days
$year = date('Y', strtotime($selected_month));
$month = date('m', strtotime($selected_month));
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$month_start = $selected_month . '-01';
$next_month_start = date('Y-m-d', strtotime($month_start . ' +1 month'));
$month_end = date('Y-m-d', strtotime($next_month_start . ' -1 day'));

// Fetch Attendance Records for this month
$stmt = $pdo->prepare("SELECT *, DATE(check_in) as log_date FROM attendance WHERE employee_id = ? AND created_at >= ? AND created_at < ? ORDER BY check_in ASC");
$stmt->execute([$employee_id, $month_start . ' 00:00:00', $next_month_start . ' 00:00:00']);
$raw_records = $stmt->fetchAll();

// Group records by date (handling multiple logs per day if any)
$attendance_map = [];
foreach ($raw_records as $record) {
    $date = $record['log_date'];
    if (!isset($attendance_map[$date])) {
        $attendance_map[$date] = [];
    }
    $attendance_map[$date][] = $record;
}

// Fetch Approved Leaves overlapping this month
$stmt = $pdo->prepare("SELECT * FROM leaves WHERE employee_id = ? AND status = 'approved' AND start_date <= ? AND end_date >= ?");
$stmt->execute([$employee_id, $month_end, $month_start]);
$leaves = $stmt->fetchAll();

// Map leaves to dates
$leave_map = [];
foreach ($leaves as $leave) {
    $start = strtotime($leave['start_date']);
    $end = strtotime($leave['end_date']);

    // Iterate through leave range
    for ($current = $start; $current <= $end; $current = strtotime('+1 day', $current)) {
        $date_str = date('Y-m-d', $current);
        // Only map if within selected month
        if (date('Y-m', $current) === $selected_month) {
            $leave_map[$date_str] = [
                'type' => $leave['leave_type'],
                'session' => $leave['day_session']
            ];
        }
    }
}

// Summary Logic
$days_present = count($attendance_map);
$days_absent = 0;
$total_seconds = 0;
$total_ot_seconds = 0;

// Helper to calculate work seconds (similar to dashboard logic)
// Helper to calculate work seconds (returning both regular and OT seconds)
function calculateWorkSeconds($checkIn, $checkOut, $shift)
{
    if (!$checkOut) return ['regular' => 0, 'ot' => 0];

    $startTime = ($shift === '830-530') ? "08:30:00" : "08:00:00";
    $endTime = ($shift === '830-530') ? "17:30:00" : "17:00:00";

    $shift_start = strtotime(date('Y-m-d', strtotime($checkIn)) . ' ' . $startTime);
    $shift_end = strtotime(date('Y-m-d', strtotime($checkIn)) . ' ' . $endTime);
    $actual_checkin = strtotime($checkIn);
    $actual_checkout = strtotime($checkOut);

    // Regular Hours: Capped between shift start and end
    $reg_start = max($actual_checkin, $shift_start);
    $reg_end = min($actual_checkout, $shift_end);
    $regular_seconds = max(0, $reg_end - $reg_start);

    // Overtime: Time worked outside the shift window
    $ot_early = max(0, $shift_start - $actual_checkin);
    $ot_late = max(0, $actual_checkout - $shift_end);
    $total_extra_seconds = $ot_early + $ot_late;

    // Apply Half-Hour Rule (Round down to nearest 30 mins)
    $ot_seconds = floor($total_extra_seconds / 1800) * 1800;

    return [
        'regular' => $regular_seconds,
        'ot' => $ot_seconds
    ];
}

// Pre-calculate days and hours
$days_on_leave = 0;
for ($d = 1; $d <= $days_in_month; $d++) {
    $current_date = sprintf("%s-%02d", $selected_month, $d);
    $day_of_week = date('N', strtotime($current_date));
    $is_weekend = ($day_of_week >= 6); // 6 for Sat, 7 for Sun
    $has_leave = isset($leave_map[$current_date]);

    if (isset($attendance_map[$current_date])) {
        foreach ($attendance_map[$current_date] as $log) {
            $times = calculateWorkSeconds($log['check_in'], $log['check_out'], $employee['working_shift']);
            $total_seconds += $times['regular'];
            $total_ot_seconds += $times['ot'];
        }
        if ($has_leave && $leave_map[$current_date]['session'] !== 'Full Day') {
            $days_on_leave += 0.5;
        }
    } elseif ($has_leave) {
        if ($leave_map[$current_date]['session'] === 'Full Day') {
            $days_on_leave += 1;
        } else {
            $days_on_leave += 0.5;
            if (!$is_weekend && strtotime($current_date) <= time()) {
                $days_absent += 0.5;
            }
        }
    } elseif (!$is_weekend && strtotime($current_date) <= time()) {
        $days_absent++;
    }
}

$h = floor($total_seconds / 3600);
$m = floor(($total_seconds % 3600) / 60);
$formatted_hours = "{$h}h {$m}m";

$oth = floor($total_ot_seconds / 3600);
$otm = floor(($total_ot_seconds % 3600) / 60);
$formatted_ot_hours = "{$oth}h {$otm}m";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - <?php echo htmlspecialchars($employee['full_name']); ?></title>
    <link rel="stylesheet" href="../../../assets/admin/shared/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 0.8rem;
            color: #737373;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-color);
        }

        .stat-card.present .value {
            color: #166534;
        }

        .stat-card.absent .value {
            color: #991b1b;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .calendar-table th {
            background: #fafafa;
            padding: 15px;
            text-align: left;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #737373;
            border-bottom: 1px solid var(--border-color);
        }

        .calendar-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }

        .weekend {
            background: #fdfdfd;
            color: #a3a3a3;
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }

        .pill-present {
            background: #dcfce7;
            color: #166534;
        }

        .pill-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .pill-weekend {
            background: #f1f5f9;
            color: #475569;
        }

        .pill-leave {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            outline: none;
        }
    </style>
</head>

<body>
    <?php
    $activePage = 'attendance';
    $baseUrl = '../';
include __DIR__ . '/../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="report-header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Monthly Attendance Report</h1>
                <p style="color: #737373;">Viewing records for <strong><?php echo htmlspecialchars($employee['full_name']); ?></strong></p>
            </div>
            <a href="attendance.php<?php echo $employee_id ? '?employee_id=' . $employee_id : ''; ?>" class="btn-primary" style="background: #737373;">
                <i class="fas fa-arrow-left"></i> Back to Attendance
            </a>
        </div>

        <form method="GET" class="filter-section">
            <div class="filter-group">
                <label>Employee</label>
                <select name="employee_id" onchange="this.form.submit()">
                    <?php foreach ($all_employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo ($emp['id'] == $employee_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Month</label>
                <input type="month" name="month" value="<?php echo $selected_month; ?>" onchange="this.form.submit()">
            </div>
        </form>

        <div class="summary-grid">
            <div class="stat-card">
                <h3>Days in Month</h3>
                <div class="value"><?php echo $days_in_month; ?></div>
            </div>
            <div class="stat-card present">
                <h3>Days Present</h3>
                <div class="value"><?php echo $days_present; ?></div>
            </div>
            <div class="stat-card absent">
                <h3>Days Absent</h3>
                <div class="value"><?php echo $days_absent; ?></div>
            </div>
            <div class="stat-card" style="border-top: 4px solid #0ea5e9;">
                <h3>Days on Leave</h3>
                <div class="value" style="color: #0369a1;"><?php echo $days_on_leave; ?></div>
            </div>
            <div class="stat-card" style="border-top: 4px solid var(--accent-color);">
                <h3>Total Regular Hours</h3>
                <div class="value"><?php echo $formatted_hours; ?></div>
            </div>
            <div class="stat-card" style="border-top: 4px solid #f59e0b;">
                <h3>Total OT Hours</h3>
                <div class="value" style="color: #d97706;"><?php echo $formatted_ot_hours; ?></div>
            </div>
        </div>

        <div class="admin-card" style="padding: 0;">
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Status</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Work Hours</th>
                        <th>OT (30m blocks)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($d = 1; $d <= $days_in_month; $d++):
                        $current_date = sprintf("%s-%02d", $selected_month, $d);
                        $timestamp = strtotime($current_date);
                        $day_name = date('l', $timestamp);
                        $is_weekend = (date('N', $timestamp) >= 6);
                        $is_future = ($timestamp > time());

                        $logs = $attendance_map[$current_date] ?? null;
                    ?>
                        <tr class="<?php echo ($is_weekend && !$logs) ? 'weekend' : ''; ?>">
                            <td style="font-weight: 600;"><?php echo date('d M Y', $timestamp); ?></td>
                            <td><?php echo $day_name; ?></td>
                            <td>
                                <?php if ($logs): ?>
                                    <span class="status-pill pill-present">PRESENT</span>
                                    <?php if (isset($leave_map[$current_date])): ?>
                                        <br><span class="status-pill pill-leave" style="margin-top: 4px; font-size: 0.65rem;">
                                            <?php echo strtoupper($leave_map[$current_date]['session']); ?> LEAVE
                                        </span>
                                    <?php endif; ?>
                                <?php elseif (isset($leave_map[$current_date])): ?>
                                    <span class="status-pill pill-leave">
                                        <?php
                                        echo ($leave_map[$current_date]['session'] === 'Full Day') ? 'ON LEAVE' : strtoupper($leave_map[$current_date]['session']) . ' LEAVE';
                                        ?>
                                    </span>
                                <?php elseif ($is_weekend): ?>
                                    <span class="status-pill pill-weekend">WEEKEND</span>
                                <?php elseif ($is_future): ?>
                                    <span style="color: #d4d4d4; font-size: 0.8rem;">-</span>
                                <?php else: ?>
                                    <span class="status-pill pill-absent">ABSENT</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($logs) {
                                    foreach ($logs as $log) {
                                        echo date('h:i A', strtotime($log['check_in'])) . "<br>";
                                    }
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($logs) {
                                    foreach ($logs as $log) {
                                        echo $log['check_out'] ? date('h:i A', strtotime($log['check_out'])) : '<span style="color: #854d0e;">Active</span>';
                                        echo "<br>";
                                    }
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($logs) {
                                    $day_seconds = 0;
                                    $day_ot_seconds = 0;
                                    foreach ($logs as $log) {
                                        $times = calculateWorkSeconds($log['check_in'], $log['check_out'], $employee['working_shift']);
                                        $day_seconds += $times['regular'];
                                        $day_ot_seconds += $times['ot'];
                                    }
                                    if ($day_seconds > 0) {
                                        $dh = floor($day_seconds / 3600);
                                        $dm = floor(($day_seconds % 3600) / 60);
                                        echo "{$dh}h {$dm}m";
                                    } else {
                                        echo "0h 0m";
                                    }
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>
                            <td style="font-weight: 700; color: #d97706;">
                                <?php
                                if ($logs && $day_ot_seconds > 0) {
                                    $doth = floor($day_ot_seconds / 3600);
                                    $dotm = floor(($day_ot_seconds % 3600) / 60);
                                    echo "{$doth}h {$dotm}m";
                                } elseif ($logs) {
                                    echo "-";
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
