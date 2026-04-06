<?php
require_once 'db_connect.php';

try {
    $stmt = $pdo->query("SELECT * FROM debug_logs ORDER BY log_time DESC LIMIT 20");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>📜 The Black Box Logs</h1>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; font-family: monospace;'>";
    echo "<tr style='background: #f1f5f9;'><th>Time</th><th>Context</th><th>Message</th><th>User ID</th></tr>";
    
    foreach ($logs as $log) {
        echo "<tr>";
        echo "<td>{$log['log_time']}</td>";
        echo "<td><b>{$log['context']}</b></td>";
        echo "<td>" . htmlspecialchars($log['message']) . "</td>";
        echo "<td>" . ($log['user_id'] ?: '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<h1>❌ Error reading logs!</h1>" . $e->getMessage();
}
?>
