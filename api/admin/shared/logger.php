<?php

/**
 * System Logger Helper
 */
function logAction($pdo, $admin_id, $admin_username, $action, $target_type, $target_id, $details)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (admin_id, admin_username, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$admin_id, $admin_username, $action, $target_type, $target_id, $details]);
        return true;
    } catch (Exception $e) {
        // Silently fail to not interrupt main flow
        return false;
    }
}
