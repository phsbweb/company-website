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
    echo "<p>I will now start recording your login attempts.</p>";
} catch (PDOException $e) {
    echo "<h1>❌ Error!</h1>";
    echo "<p>Could not create table: " . $e->getMessage() . "</p>";
}
?>
