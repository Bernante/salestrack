<?php
require 'config/database.php';
try {
    $db = getDBConnection();
    $result = $db->query('SELECT COUNT(*) as cnt FROM users')->fetch();
    echo "✓ Database connection successful!\n";
    echo "Users in database: " . $result['cnt'] . "\n";
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
}
