<?php
/**
 * Database Connection Test - Upload this to InfinityFree to diagnose DB issues
 */

echo "<h2>Database Connection Test</h2>";
echo "<p>Testing connection to InfinityFree MySQL database...</p>";

// Database credentials
$db_host = '127.0.0.1';
$db_name = 'if0_42783325_salestrack2';
$db_user = 'if0_42783325';
$db_pass = 'Patrick121603';
$db_port = 3306;

try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "<p style='color: green;'><strong>✅ SUCCESS:</strong> Connected to database!</p>";
    
    // List tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Tables found: " . count($tables) . "</strong></p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>❌ ERROR:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Make sure the database exists and credentials are correct.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ ERROR:</strong> " . $e->getMessage() . "</p>";
}

echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
?>