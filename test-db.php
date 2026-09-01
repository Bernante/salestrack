<?php
/**
 * Database Connection Test
 * Run this at: http://localhost/inventory/test-db.php
 */

// Include database config
require_once __DIR__ . '/config/database.php';

echo "<h1>Database Connection Test</h1>";
echo "<hr>";

// Show credentials being used
echo "<h3>Credentials:</h3>";
echo "<pre>";
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";
echo "Password: " . (DB_PASS ? "***" : "[EMPTY]") . "\n";
echo "</pre>";

echo "<h3>Connection Test:</h3>";

try {
    // Try to connect
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "<p style='color: green;'><strong>✅ MySQL Connection: SUCCESS!</strong></p>";
    
    // Try to select database
    $pdo->exec("USE " . DB_NAME);
    echo "<p style='color: green;'><strong>✅ Database Selected: SUCCESS!</strong></p>";
    
    // Count tables
    $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $count = $result->fetch()['count'];
    
    echo "<p><strong>Tables in database:</strong> $count</p>";
    
    // List tables
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<p><strong>Tables found:</strong></p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ No tables found. Database is empty.</strong></p>";
    }
    
    echo "<hr>";
    echo "<p style='color: green;'><strong>🎉 Database connection is working!</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>❌ Connection Failed!</strong></p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<hr>";
    echo "<h3>How to Fix:</h3>";
    echo "<ol>";
    echo "<li>Make sure WAMP MySQL is running (green icon in system tray)</li>";
    echo "<li>Check that the database name is correct: " . DB_NAME . "</li>";
    echo "<li>Check that the username is correct: " . DB_USER . "</li>";
    echo "<li>If password is wrong, update database.local.php</li>";
    echo "</ol>";
}

?>
