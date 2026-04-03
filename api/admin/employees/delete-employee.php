<?php
include '../shared/auth.php';
require_once '../../user/attendance/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        // Attendance records will be deleted automatically due to ON DELETE CASCADE
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success_msg'] = "Employee deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error deleting employee: " . $e->getMessage();
    }
}

header("Location: employees.php");
exit;
