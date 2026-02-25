<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?trace=no_session");
    exit;
}

require_once 'db_connect.php';

// Get current attendance status
$employee_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$employee_id]);
$current_status = $stmt->fetch();

$isCheckedIn = ($current_status && $current_status['status'] === 'checked_in');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance System</title>
    <link rel="stylesheet" href="style.css">
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

        <div class="status-badge <?php echo $isCheckedIn ? 'status-checked-in' : 'status-checked-out'; ?>" id="status-badge">
            Status: <?php echo $isCheckedIn ? 'Checked In' : 'Checked Out'; ?>
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

    <script src="script.js"></script>
</body>

</html>