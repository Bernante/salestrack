<?php
/**
 * Automated Database Migration Runner
 * 
 * Automatically runs all pending SQL migrations without manual phpMyAdmin import
 * 
 * Usage:
 * 1. Create migration files: database/migrations/YYYY-MM-DD-description.sql
 * 2. Push to GitHub: git push origin main
 * 3. Visit: https://yourdomain.com/database/migrate.php
 * 4. Migrations run automatically!
 * 
 * Example migration files:
 * - database/migrations/2026-08-31-add-sale-date.sql
 * - database/migrations/2026-09-01-add-notes.sql
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    // Create migrations tracking table
    $db->exec("
        CREATE TABLE IF NOT EXISTS _migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'success'
        )
    ");
    
    echo "=== AUTOMATED DATABASE MIGRATION SYSTEM ===\n\n";
    
    // Check if migrations folder exists
    $migrationDir = __DIR__ . '/migrations';
    
    if (!is_dir($migrationDir)) {
        echo "✓ No migrations folder found. Creating...\n";
        mkdir($migrationDir, 0755, true);
        echo "✓ Migrations folder created at: database/migrations/\n";
        echo "\nTo add migrations:\n";
        echo "1. Create .sql files in database/migrations/\n";
        echo "2. Name them: YYYY-MM-DD-description.sql\n";
        echo "3. Run this file again\n";
        exit(0);
    }
    
    // Scan for migration files
    $files = scandir($migrationDir);
    
    // Filter for migration files: YYYY-MM-DD-*.sql
    $migrations = array_filter($files, function($file) {
        return preg_match('/^\d{4}-\d{2}-\d{2}-.+\.sql$/', $file);
    });
    
    sort($migrations);
    
    if (empty($migrations)) {
        echo "✓ No pending migrations.\n";
        echo "All systems up to date!\n";
        exit(0);
    }
    
    $executed = 0;
    $skipped = 0;
    $failed = 0;
    
    foreach ($migrations as $migrationFile) {
        // Check if migration already executed
        $stmt = $db->prepare("SELECT id FROM _migrations WHERE migration_name = ?");
        $stmt->execute([$migrationFile]);
        
        if ($stmt->fetch()) {
            echo "⊘ SKIPPED: $migrationFile (already executed)\n";
            $skipped++;
            continue;
        }
        
        // Read migration SQL file
        $sqlPath = $migrationDir . '/' . $migrationFile;
        $sqlContent = file_get_contents($sqlPath);
        
        if ($sqlContent === false) {
            echo "✗ ERROR: Cannot read $migrationFile\n";
            $failed++;
            continue;
        }
        
        try {
            // Split SQL statements by semicolon and execute
            $statements = array_filter(
                array_map('trim', explode(';', $sqlContent)),
                function($s) { return !empty(trim($s)); }
            );
            
            foreach ($statements as $sql) {
                $db->exec($sql);
            }
            
            // Record migration as executed
            $insertStmt = $db->prepare(
                "INSERT INTO _migrations (migration_name, status) VALUES (?, 'success')"
            );
            $insertStmt->execute([$migrationFile]);
            
            echo "✓ EXECUTED: $migrationFile\n";
            $executed++;
            
        } catch (PDOException $e) {
            // Record failed migration
            $insertStmt = $db->prepare(
                "INSERT INTO _migrations (migration_name, status) VALUES (?, 'failed')"
            );
            $insertStmt->execute([$migrationFile]);
            
            echo "✗ FAILED: $migrationFile\n";
            echo "  Error: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
    
    echo "\n=== MIGRATION SUMMARY ===\n";
    echo "Executed: $executed\n";
    echo "Skipped:  $skipped\n";
    echo "Failed:   $failed\n";
    
    if ($failed > 0) {
        echo "\n⚠ Some migrations failed. Check errors above.\n";
        exit(1);
    } else {
        echo "\n✓ All migrations completed successfully!\n";
    }
    
} catch (Exception $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
