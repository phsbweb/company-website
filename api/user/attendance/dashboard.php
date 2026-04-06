<?php
session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();

// DEBUG: Spy at the front door!
if (isset($_GET['debug'])) {
    echo "<pre>DEBUG FRONT DOOR:\n";
    echo "SESSION:\n"; print_r($_SESSION);
    echo "\nCOOKIES:\n"; print_r($_COOKIE);
    echo "</pre>";
    // exit; // Uncomment to stop here if needed
}

if (!isset($_SESSION['user_id'])) {
    // If no session, try to re-hydrate from cookie (Vercel/Serverless fix)
    if (isset($_COOKIE['device_token'])) {
        require_once 'db_connect.php';
        log_debug($pdo, 'DASHBOARD', "No session. Re-hydrating from cookie: " . $_COOKIE['device_token']);

        $token = $_COOKIE['device_token'];
        $stmt = $pdo->prepare("SELECT e.* FROM employees e JOIN device_tokens dt ON e.id = dt.employee_id WHERE dt.token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            log_debug($pdo, 'DASHBOARD', "SUCCESS: Re-hydrated user ID: " . $user['id'], $user['id']);
        } else {
            log_debug($pdo, 'DASHBOARD', "FAIL: Token not found in database.");
            header("Location: index.php?trace=no_session");
            exit;
        }
    } else {
        header("Location: index.php?trace=no_session");
        exit;
    }
} else {
    require_once 'db_connect.php';
    log_debug($pdo, 'DASHBOARD', "Session verified. User ID: " . $_SESSION['user_id'], $_SESSION['user_id']);
}

require_once 'db_connect.php';

// Get current attendance status
$employee_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT a.*, e.working_shift FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.employee_id = ? ORDER BY a.created_at DESC LIMIT 1");
$stmt->execute([$employee_id]);
$current_status = $stmt->fetch();

if ($current_status && $current_status['status'] === 'checked_in') {
    $checkin_date = date('Y-m-d', strtotime($current_status['check_in']));
    $today = date('Y-m-d');

    if ($checkin_date < $today) {
        // Auto-fix stale check-in from previous day
        $shift_end_time = ($current_status['working_shift'] === '830-530') ? "17:30:00" : "17:00:00";
        $auto_checkout = $checkin_date . ' ' . $shift_end_time;

        $update = $pdo->prepare("UPDATE attendance SET check_out = ?, status = 'checked_out', location_out = 'Auto-Checkout (System)' WHERE id = ?");
        $update->execute([$auto_checkout, $current_status['id']]);

        // Auto Logout: Clear device token and destroy session
        if (isset($_COOKIE['device_token'])) {
            $stmt = $pdo->prepare("DELETE FROM device_tokens WHERE token = ?");
            $stmt->execute([$_COOKIE['device_token']]);
            setcookie('device_token', '', time() - 3600, '/');
        }

        session_destroy();
        header("Location: index.php?trace=auto_logout");
        exit;
    } else {
        $isCheckedIn = true;
    }
} else {
    $isCheckedIn = false;
}

// Prepare shift data for reminders
$shift_start = ($current_status['working_shift'] === '830-530') ? "08:30" : "08:00";
$shift_end = ($current_status['working_shift'] === '830-530') ? "17:30" : "17:00";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance System</title>
    <link rel="stylesheet" href="../../../assets/user/attendance/style.css">
    <style>
        .late-badge {
            background: #fef2f2;
            color: #991b1b;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 8px;
            border: 1px solid #fee2e2;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>

<body>
    <div class="container dashboard-container">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div style="text-align: left;">
                <h2 style="font-size: 1.25rem;">Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <p style="color: #64748b; font-size: 0.875rem;">Company Attendance System</p>
                    <span style="color: #cbd5e1;">|</span>
                    <a href="change_password.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.875rem;">Change Password</a>
                </div>
            </div>
            <form action="auth.php" method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" id="logout-btn" class="btn btn-secondary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.875rem;"
                    <?php echo $isCheckedIn ? 'disabled title="Please check out before logging out"' : ''; ?>>
                    Logout
                </button>
            </form>
        </header>

        <div class="nav-tabs" style="display: flex; gap: 10px; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
            <a href="dashboard.php" class="nav-tab active" style="padding: 0.5rem 1rem; text-decoration: none; color: var(--primary-color); font-weight: 500; border-radius: 6px; background: #f1f5f9;">Attendance</a>
            <a href="leaves.php" class="nav-tab" style="padding: 0.5rem 1rem; text-decoration: none; color: #64748b; font-weight: 500; border-radius: 6px; transition: all 0.2s;">Leave Requests</a>
            <a href="directory.php" class="nav-tab" style="padding: 0.5rem 1rem; text-decoration: none; color: #64748b; font-weight: 500; border-radius: 6px; transition: all 0.2s;">Directory</a>
        </div>

        <div class="status-badge <?php echo $isCheckedIn ? 'status-checked-in' : 'status-checked-out'; ?>" id="status-badge">
            Status: <?php echo $isCheckedIn ? 'Checked In' : 'Checked Out'; ?>
            <?php if ($isCheckedIn && ($current_status['is_late'] ?? 0) == 1): ?>
                <span class="late-badge">Late</span>
            <?php endif; ?>
        </div>

        <div>
            <button id="attendance-btn" class="btn <?php echo $isCheckedIn ? 'btn-danger' : ''; ?>"
                data-action="<?php echo $isCheckedIn ? 'checkout' : 'checkin'; ?>">
                <?php echo $isCheckedIn ? 'Check Out' : 'Check In'; ?>
            </button>
        </div>

        <p class="info-text" id="time-display">Current Time: --:--:--</p>

        <?php if ($isCheckedIn && !empty($current_status['location_in'])): ?>
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #64748b;">
                <strong>Check-in Location:</strong> <?php echo htmlspecialchars($current_status['location_in']); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal-overlay">
        <div class="modal">
            <h3>Confirm Check Out</h3>
            <p style="margin-top: 1rem; color: #64748b;">Are you sure you want to check out for today?</p>
            <div class="modal-actions">
                <button id="cancel-btn" class="btn btn-secondary">Cancel</button>
                <button id="confirm-checkout-btn" class="btn btn-danger">Yes, Check Out</button>
            </div>
        </div>
    </div>

    <script>
        window.attendanceData = {
            isCheckedIn: <?php echo $isCheckedIn ? 'true' : 'false'; ?>,
            shiftStart: "<?php echo $shift_start; ?>",
            shiftEnd: "<?php echo $shift_end; ?>",
            userId: <?php echo $_SESSION['user_id']; ?>
        };
    </script>
    <script src="../../../assets/user/attendance/script.js"></script>
    <script src="../../../assets/user/attendance/reminders.js"></script>
</body>

</html>