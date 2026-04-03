<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?trace=no_session");
    exit;
}

require_once 'db_connect.php';

$employee_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Fetch All Holiday Ranges
$stmt_h = $pdo->query("SELECT start_date, end_date FROM holidays");
$all_holiday_ranges = $stmt_h->fetchAll();
$holidays_json = json_encode($all_holiday_ranges);

// Fetch Entitlements & Calculate Balances
$stmt = $pdo->prepare("SELECT annual_leave_entitlement, medical_leave_entitlement, working_shift FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$entitlement_raw = $stmt->fetch();
$annual_entitlement = $entitlement_raw['annual_leave_entitlement'] ?? 18;

// Get current attendance status for reminders
$stmt_att = $pdo->prepare("SELECT status, check_in FROM attendance WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt_att->execute([$employee_id]);
$current_att = $stmt_att->fetch();
$isCheckedIn = false;
if ($current_att && $current_att['status'] === 'checked_in') {
    if (date('Y-m-d', strtotime($current_att['check_in'])) === date('Y-m-d')) {
        $isCheckedIn = true;
    }
}

$working_shift = $entitlement_raw['working_shift'] ?? '800-500';
$shift_start = ($working_shift === '830-530') ? "08:30" : "08:00";
$shift_end = ($working_shift === '830-530') ? "17:30" : "17:00";

// Fetch taken annual leave (approved)
$stmt = $pdo->prepare("SELECT SUM(total_days) as taken FROM leaves WHERE employee_id = ? AND leave_type = 'Annual' AND status = 'approved'");
$stmt->execute([$employee_id]);
$taken_annual = $stmt->fetch()['taken'] ?? 0;

// Fetch applied annual leave (pending)
$stmt = $pdo->prepare("SELECT SUM(total_days) as applied FROM leaves WHERE employee_id = ? AND leave_type = 'Annual' AND status = 'pending'");
$stmt->execute([$employee_id]);
$applied_annual = $stmt->fetch()['applied'] ?? 0;

$balance_annual = $annual_entitlement - $taken_annual;

// Fetch taken medical/sick leave (approved)
$stmt = $pdo->prepare("SELECT SUM(total_days) as taken FROM leaves WHERE employee_id = ? AND leave_type IN ('Sick', 'Medical') AND status = 'approved'");
$stmt->execute([$employee_id]);
$taken_medical = $stmt->fetch()['taken'] ?? 0;

// Fetch applied medical/sick leave (pending)
$stmt = $pdo->prepare("SELECT SUM(total_days) as applied FROM leaves WHERE employee_id = ? AND leave_type IN ('Sick', 'Medical') AND status = 'pending'");
$stmt->execute([$employee_id]);
$applied_medical = $stmt->fetch()['applied'] ?? 0;

$medical_entitlement = $entitlement_raw['medical_leave_entitlement'] ?? 14;
$balance_medical = $medical_entitlement - $taken_medical;

// Handle Leave Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_leave') {
    $leave_type = $_POST['leave_type'] ?? '';
    $day_session = $_POST['day_session'] ?? 'Full Day';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = ($day_session !== 'Full Day') ? $start_date : ($_POST['end_date'] ?? '');
    $reason = $_POST['reason'] ?? '';

    // Calculate total days (excluding weekends and holidays)
    if ($day_session !== 'Full Day') {
        $total_days = 0.5;
    } elseif (!empty($start_date) && !empty($end_date)) {
        $total_days = 0;
        $current = strtotime($start_date);
        $end = strtotime($end_date);

        while ($current <= $end) {
            $date_str = date('Y-m-d', $current);
            $day_of_week = date('N', $current);
            $is_weekend = ($day_of_week >= 6); // 6=Sat, 7=Sun
            $is_holiday = false;
            foreach ($all_holiday_ranges as $range) {
                if ($date_str >= $range['start_date'] && $date_str <= $range['end_date']) {
                    $is_holiday = true;
                    break;
                }
            }

            if (!$is_weekend && !$is_holiday) {
                $total_days++;
            }
            $current = strtotime('+1 day', $current);
        }
    } else {
        $total_days = 0;
    }

    if (empty($leave_type) || empty($start_date) || empty($end_date)) {
        $error = "Please fill in all required fields.";
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error = "Start date cannot be after end date.";
    } else {
        try {
            $document_path = NULL;
            if (isset($_FILES['mc_file']) && $_FILES['mc_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['mc_file']['tmp_name'];
                $file_name = time() . '_' . basename($_FILES['mc_file']['name']);
                $upload_dir = 'uploads/mc/';
                $document_path = $upload_dir . $file_name;
                move_uploaded_file($file_tmp, $document_path);
            }

            $stmt = $pdo->prepare("INSERT INTO leaves (employee_id, leave_type, start_date, end_date, reason, day_session, total_days, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$employee_id, $leave_type, $start_date, $end_date, $reason, $day_session, $total_days, $document_path]);
            $message = "Leave request submitted successfully.";
            // Refresh to show new history items
            $_SESSION['success_msg'] = $message;
            header("Location: leaves.php");
            exit;
        } catch (PDOException $e) {
            $error = "Failed to submit request: " . $e->getMessage();
        }
    }
}

// Handle Leave Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_leave') {
    $leave_id = $_POST['leave_id'] ?? '';
    if (!empty($leave_id)) {
        try {
            // Ensure the leave belongs to the user and is still pending
            $stmt = $pdo->prepare("UPDATE leaves SET status = 'cancelled' WHERE id = ? AND employee_id = ? AND status = 'pending'");
            $stmt->execute([$leave_id, $employee_id]);

            if ($stmt->rowCount() > 0) {
                // Log action
                require_once '../../admin/shared/logger.php';
                logAction($pdo, $employee_id, $_SESSION['full_name'], 'Cancel Leave', 'Leave', $leave_id, "Employee cancelled their own pending leave request");

                $_SESSION['success_msg'] = "Leave request cancelled successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to cancel leave or it's no longer pending.";
            }
            header("Location: leaves.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Leave History
$stmt = $pdo->prepare("SELECT * FROM leaves WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$employee_id]);
$leaves = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - Attendance System</title>
    <link rel="stylesheet" href="../../../user/attendance/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        .nav-tab {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-tab.active {
            color: var(--primary-color);
            background: #f1f5f9;
        }

        .leave-form {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            text-align: left;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            font-size: 0.95rem;
        }

        .leave-history {
            text-align: left;
        }

        .leave-history h3 {
            font-size: 1.125rem;
            margin-bottom: 1rem;
            color: #1e293b;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .history-table th,
        .history-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .history-table th {
            color: #64748b;
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-cancelled {
            background: #f1f5f9;
            color: #64748b;
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .btn-cancel {
            background: #fff;
            color: #ef4444;
            border: 1px solid #fee2e2;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            display: inline-block;
            min-width: 80px;
            text-align: center;
            margin-top: 5px;
        }

        .btn-cancel:hover {
            background: #fef2f2;
            border-color: #fecaca;
        }

        /* Entitlement Info Card */
        .entitlement-card {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: none;
            /* Hidden by default, shown via JS */
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .entitlement-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .entitlement-item {
            background: #fff;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .entitlement-item span {
            display: block;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .entitlement-item strong {
            font-size: 1.125rem;
            color: #1e293b;
        }
    </style>
</head>

<body>
    <div class="container" style="max-width: 600px;">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div style="text-align: left;">
                <h2 style="font-size: 1.25rem;">Leaves Management</h2>
                <p style="color: #64748b; font-size: 0.875rem;">Submit and track your leave requests</p>
            </div>
            <a href="dashboard.php" class="nav-tab"><i class="fas fa-arrow-left"></i> Back</a>
        </header>

        <div class="nav-tabs">
            <a href="dashboard.php" class="nav-tab">Attendance</a>
            <a href="leaves.php" class="nav-tab active">Leave Requests</a>
            <a href="directory.php" class="nav-tab">Directory</a>
        </div>

        <?php if ($message): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: left;">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: left;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="leave-form">
            <h3>New Request</h3>
            <form action="leaves.php" method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="submit_leave">
                <div class="form-group">
                    <label>Leave Type</label>
                    <select name="leave_type" id="leave_type" required onchange="toggleEntitlementInfo()">
                        <option value="">Select Type</option>
                        <option value="Annual">Annual Leave</option>
                        <option value="Emergency">Emergency Leave</option>
                        <option value="Advance">Advance Leave</option>
                        <option value="Unpaid">Unpaid Leave</option>
                        <option value="Medical">Medical Leave</option>
                        <option value="Marriage">Marriage Leave</option>
                        <option value="Compassionate">Compassionate Leave</option>
                        <option value="Maternity">Maternity Leave</option>
                        <option value="Other">Others (please specify)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Duration Type</label>
                    <select name="day_session" id="day_session" onchange="handleSessionChange()" required>
                        <option value="Full Day">Full Day</option>
                        <option value="Morning">Half Day (Morning)</option>
                        <option value="Afternoon">Half Day (Afternoon)</option>
                    </select>
                </div>

                <!-- Entitlement Info Section -->
                <div id="entitlement-info" class="entitlement-card">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div id="entitlement-title" style="display: flex; align-items: center; gap: 8px; color: #1e40af; font-weight: 700; font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> <span id="title-text">Annual Leave</span> Info (<?php echo date('Y'); ?>)
                        </div>
                        <div id="this-app-badge" style="background: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; border: 1px solid #dbeafe; display: none;">
                            This Application: <span id="app-days">0</span> day(s)
                        </div>
                    </div>
                    <div class="entitlement-grid">
                        <div class="entitlement-item">
                            <span>Entitlement</span>
                            <strong id="val-entitlement">0</strong>
                        </div>
                        <div class="entitlement-item">
                            <span>Applied</span>
                            <strong id="val-applied" style="color: #b45309;">0</strong>
                        </div>
                        <div class="entitlement-item">
                            <span id="label-taken">Accumulated Taken</span>
                            <strong id="val-taken" style="color: #64748b;">0</strong>
                        </div>
                        <div class="entitlement-item" style="border-color: #93c5fd; background: #f0f7ff;">
                            <span>Balance for Year</span>
                            <strong id="val-balance" style="color: #2563eb;">0</strong>
                        </div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label id="start_label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required min="<?php echo date('Y-m-d'); ?>" onchange="calculateAppDays()">
                    </div>
                    <div class="form-group" id="end_date_group">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="end_date" required min="<?php echo date('Y-m-d'); ?>" onchange="calculateAppDays()">
                    </div>
                </div>
                <div class="form-group">
                    <label id="reason_label">Reason</label>
                    <textarea name="reason" id="reason" rows="3" placeholder="Explain your reason for leave..."></textarea>
                </div>
                <div class="form-group" id="mc_upload_group" style="display: none;">
                    <label>Attachment (MC / Supporting Document)</label>
                    <input type="file" name="mc_file" id="mc_file" accept=".jpg,.jpeg,.png,.pdf">
                    <small style="color: #64748b;">Allowed types: JPG, PNG, PDF</small>
                </div>
                <button type="submit" class="btn-submit">Submit Request</button>
            </form>
        </div>

        <div class="leave-history">
            <h3>Your History</h3>
            <?php if (empty($leaves)): ?>
                <div style="text-align: center; padding: 2rem; color: #94a3b8; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <i class="fas fa-calendar-minus" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                    <p>No leave requests found.</p>
                </div>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Dates</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($leave['leave_type']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo date('d M', strtotime($leave['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;">
                                        <?php if ($leave['day_session'] !== 'Full Day'): ?>
                                            <?php echo date('d M Y', strtotime($leave['start_date'])); ?>
                                            <span style="display: block; font-size: 0.75rem; color: #2563eb; font-weight: 600;">(<?php echo $leave['day_session']; ?>)</span>
                                        <?php else: ?>
                                            <?php echo date('d M', strtotime($leave['start_date'])); ?> - <?php echo date('d M', strtotime($leave['end_date'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">
                                        <?php echo number_format($leave['total_days'], 1); ?> day(s)
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                        <span class="status-badge status-<?php echo $leave['status']; ?>" style="margin: 0; display: block;">
                                            <?php echo $leave['status']; ?>
                                        </span>
                                        <?php if ($leave['status'] === 'pending'): ?>
                                            <form action="leaves.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this leave request?');" style="margin: 0; line-height: 1.2;">
                                                <input type="hidden" name="action" value="cancel_leave">
                                                <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                                <button type="submit" class="btn-cancel" style="margin: 0;">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <script>
        const leaveData = {
            'Annual': {
                entitlement: <?php echo $annual_entitlement; ?>,
                applied: <?php echo $applied_annual; ?>,
                taken: <?php echo $taken_annual; ?>,
                balance: <?php echo $balance_annual; ?>,
                title: 'Annual Leave'
            },
            'Emergency': {
                entitlement: <?php echo $annual_entitlement; ?>,
                applied: <?php echo $applied_annual; ?>,
                taken: <?php echo $taken_annual; ?>,
                balance: <?php echo $balance_annual; ?>,
                title: 'Annual Leave (Emergency)'
            },
            'Advance': {
                entitlement: <?php echo $annual_entitlement; ?>,
                applied: <?php echo $applied_annual; ?>,
                taken: <?php echo $taken_annual; ?>,
                balance: <?php echo $balance_annual; ?>,
                title: 'Annual Leave (Advance)'
            },
            'Medical': {
                entitlement: <?php echo $medical_entitlement; ?>,
                applied: <?php echo $applied_medical; ?>,
                taken: <?php echo $taken_medical; ?>,
                balance: <?php echo $balance_medical; ?>,
                title: 'Medical Leave'
            }
        };

        const holidays = <?php echo $holidays_json; ?>;

        function toggleEntitlementInfo() {
            const select = document.getElementById('leave_type');
            const info = document.getElementById('entitlement-info');
            const reasonLabel = document.getElementById('reason_label');
            const reasonText = document.getElementById('reason');
            const type = select.value;

            if (leaveData[type]) {
                document.getElementById('title-text').innerText = leaveData[type].title;
                document.getElementById('label-taken').innerText = (type === 'Annual' || type === 'Emergency' || type === 'Advance') ? 'Accumulated Taken' : 'Accumulated MC Taken';
                document.getElementById('val-entitlement').innerText = leaveData[type].entitlement;
                document.getElementById('val-applied').innerText = leaveData[type].applied;
                document.getElementById('val-taken').innerText = leaveData[type].taken;
                document.getElementById('val-balance').innerText = leaveData[type].balance;
                info.style.display = 'block';
            } else {
                info.style.display = 'none';
            }

            if (type === 'Medical' || type === 'Hospitalization') {
                document.getElementById('mc_upload_group').style.display = 'block';
            } else {
                document.getElementById('mc_upload_group').style.display = 'none';
            }

            if (type === 'Other') {
                reasonLabel.innerHTML = 'Reason <span style="color: #ef4444;">(Please specify)</span>';
                reasonText.placeholder = 'Please specify the type of leave and reason...';
                reasonText.required = true;
            } else {
                reasonLabel.innerText = 'Reason';
                reasonText.placeholder = 'Explain your reason for leave...';
                reasonText.required = false;
            }

            calculateAppDays();
        }

        function handleSessionChange() {
            const session = document.getElementById('day_session').value;
            const endDateGroup = document.getElementById('end_date_group');
            const endDateInput = document.getElementById('end_date');
            const startLabel = document.getElementById('start_label');

            if (session !== 'Full Day') {
                endDateGroup.style.display = 'none';
                endDateInput.required = false;
                startLabel.innerText = 'Date';
            } else {
                endDateGroup.style.display = 'block';
                endDateInput.required = true;
                startLabel.innerText = 'Start Date';
            }
            calculateAppDays();
        }

        function calculateAppDays() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            const session = document.getElementById('day_session').value;
            const badge = document.getElementById('this-app-badge');
            const display = document.getElementById('app-days');

            if (session !== 'Full Day') {
                if (start) {
                    const d = new Date(start);
                    const dayOfWeek = d.getDay(); // 0=Sun, 6=Sat
                    const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
                    let isHoliday = false;
                    for (let range of holidays) {
                        if (start >= range.start_date && start <= range.end_date) {
                            isHoliday = true;
                            break;
                        }
                    }

                    if (isWeekend || isHoliday) {
                        display.innerText = "0";
                    } else {
                        display.innerText = "0.5";
                    }
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
                return;
            }

            if (start && end) {
                const startDate = new Date(start);
                const endDate = new Date(end);
                if (endDate >= startDate) {
                    let diffDays = 0;
                    let current = new Date(startDate);

                    while (current <= endDate) {
                        const dateStr = current.toISOString().split('T')[0];
                        const dayOfWeek = current.getDay(); // 0=Sun, 6=Sat
                        const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
                        let isHoliday = false;
                        for (let range of holidays) {
                            if (dateStr >= range.start_date && dateStr <= range.end_date) {
                                isHoliday = true;
                                break;
                            }
                        }

                        if (!isWeekend && !isHoliday) {
                            diffDays++;
                        }
                        current.setDate(current.getDate() + 1);
                    }

                    display.innerText = diffDays;
                    badge.style.display = 'block';
                    return;
                }
            }
            badge.style.display = 'none';
        }
    </script>
    <script>
        window.attendanceData = {
            isCheckedIn: <?php echo $isCheckedIn ? 'true' : 'false'; ?>,
            shiftStart: "<?php echo $shift_start; ?>",
            shiftEnd: "<?php echo $shift_end; ?>",
            userId: <?php echo $_SESSION['user_id']; ?>
        };
    </script>
    <script src="reminders.js"></script>
</body>

</html>