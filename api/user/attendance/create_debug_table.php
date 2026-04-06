<?php
require_once 'db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS debug_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        context VARCHAR(50),
        message TEXT,
        user_id INT NULL,
        ip_address VARCHAR(45)
    )";
    $pdo->exec($sql);
    
    echo "<h1>✅ Success!</h1>";
    echo "<p>The <b>debug_logs</b> table is now ready in your Aiven database.</p>";
    
    // Diagnostic info
    $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>Connected to DB: <b>$db_name</b></p>";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Existing Tables: " . implode(', ', $tables) . "</p>";

} catch (PDOException $e) {
    echo "<h1>❌ Error!</h1>";
    echo "<p>Connection failed: " . $e->getMessage() . "</p>";
}
?>
