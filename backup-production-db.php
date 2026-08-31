<?php
/**
 * Production Database Backup Script
 * Backs up if0_42783325_salestrack2 database
 * Safe - READ ONLY operation
 */

// Production credentials (from database.php)
$dbHost = '127.0.0.1';
$dbName = 'if0_42783325_salestrack2';
$dbUser = 'if0_42783325';
$dbPass = 'Patrick121603';

echo "=== PRODUCTION DATABASE BACKUP ===\n";
echo "Database: $dbName\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Connected to production database\n\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Tables found: " . count($tables) . "\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
        $count = $stmt->fetch()['cnt'];
        echo "  - $table: $count records\n";
    }
    
    // Create backup SQL file
    $backupFile = __DIR__ . '/backups/production-backup-' . date('Y-m-d-H-i-s') . '.sql';
    if (!is_dir(__DIR__ . '/backups')) {
        mkdir(__DIR__ . '/backups', 0755, true);
    }
    
    $backupContent = "-- Production Database Backup\n";
    $backupContent .= "-- Database: $dbName\n";
    $backupContent .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $backupContent .= "-- WARNING: This is a sensitive backup file\n\n";
    
    foreach ($tables as $table) {
        $backupContent .= "\n-- Table: $table\n";
        
        // Get CREATE TABLE statement
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        $createTable = $stmt->fetch();
        $backupContent .= $createTable['Create Table'] . ";\n\n";
        
        // Get all data
        $stmt = $pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $columnList = implode('`, `', $columns);
            
            foreach ($rows as $row) {
                $values = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote($v);
                }, $row);
                
                $backupContent .= "INSERT INTO `$table` (`$columnList`) VALUES (" . implode(', ', $values) . ");\n";
            }
        }
    }
    
    file_put_contents($backupFile, $backupContent);
    
    echo "\n✅ Backup created: $backupFile\n";
    echo "📦 Backup size: " . filesize($backupFile) / 1024 . " KB\n";
    echo "\n✅ DATABASE BACKUP COMPLETE - SAFE TO DEPLOY\n";
    
} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
