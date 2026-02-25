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
            $stmt = $pdo->prepare("SELECT status FROM attendance WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$employee_id]);
            $last_record = $stmt->fetch();

            if ($last_record && $last_record['status'] === 'checked_in') {
                echo json_encode(['success' => false, 'message' => 'Already checked in']);
                exit;
            }

            $location = $_POST['location'] ?? 'Unknown';
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, check_in, location_in, status) VALUES (?, ?, ?, 'checked_in')");
            $stmt->execute([$employee_id, $now, $location]);
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
