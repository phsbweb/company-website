<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $employee_id = $_SESSION['user_id'];
    $action = $_POST['action'];
    $now = date('Y-m-d H:i:s');

    try {
        if ($action === 'checkin') {
            // Check if already checked in
            $stmt = $pdo->prepare("SELECT a.*, e.working_shift FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.employee_id = ? ORDER BY a.created_at DESC LIMIT 1");
            $stmt->execute([$employee_id]);
            $last_record = $stmt->fetch();

            if ($last_record && $last_record['status'] === 'checked_in') {
                $checkin_date = date('Y-m-d', strtotime($last_record['check_in']));
                $today = date('Y-m-d');

                if ($checkin_date < $today) {
                    // Auto-fix stale check-in before new check-in
                    $shift_end_time = ($last_record['working_shift'] === '830-530') ? "17:30:00" : "17:00:00";
                    $auto_checkout = $checkin_date . ' ' . $shift_end_time;

                    $update = $pdo->prepare("UPDATE attendance SET check_out = ?, status = 'checked_out', location_out = 'Auto-Checkout (System)' WHERE id = ?");
                    $update->execute([$auto_checkout, $last_record['id']]);

                    // Auto Logout: Clear device token and destroy session
                    if (isset($_COOKIE['device_token'])) {
                        $stmt = $pdo->prepare("DELETE FROM device_tokens WHERE token = ?");
                        $stmt->execute([$_COOKIE['device_token']]);
                        setcookie('device_token', '', time() - 3600, '/');
                    }
                    session_destroy();
                    echo json_encode(['success' => false, 'redirect' => 'index.php?trace=auto_logout', 'message' => 'Session expired. Please login again.']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Already checked in']);
                    exit;
                }
            }

            $location = $_POST['location'] ?? 'Unknown';
            
            // Calculate if late (15 min grace period)
            $is_late = 0;
            $shift_info = $pdo->prepare("SELECT working_shift FROM employees WHERE id = ?");
            $shift_info->execute([$employee_id]);
            $emp_shift = $shift_info->fetchColumn();
            
            $start_time_str = ($emp_shift === '830-530') ? "08:30:00" : "08:00:00";
            $grace_minutes = 15;
            $shift_start_timestamp = strtotime(date('Y-m-d ') . $start_time_str);
            $late_threshold = $shift_start_timestamp + ($grace_minutes * 60);
            
            if (strtotime($now) > $late_threshold) {
                $is_late = 1;
            }

            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, check_in, location_in, status, is_late) VALUES (?, ?, ?, 'checked_in', ?)");
            $stmt->execute([$employee_id, $now, $location, $is_late]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'checkout') {
            // Find the last check-in record
            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND status = 'checked_in' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$employee_id]);
            $record = $stmt->fetch();

            if (!$record) {
                echo json_encode(['success' => false, 'message' => 'No active check-in found']);
                exit;
            }

            $location = $_POST['location'] ?? 'Unknown';
            $stmt = $pdo->prepare("UPDATE attendance SET check_out = ?, location_out = ?, status = 'checked_out' WHERE id = ?");
            $stmt->execute([$now, $location, $record['id']]);

            // Clear device token on checkout
            if (isset($_COOKIE['device_token'])) {
                $stmt = $pdo->prepare("DELETE FROM device_tokens WHERE token = ?");
                $stmt->execute([$_COOKIE['device_token']]);
                setcookie('device_token', '', time() - 3600, '/');
            }

            // Auto logout: destroy session
            session_destroy();

            echo json_encode(['success' => true]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
