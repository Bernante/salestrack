<?php
/**
 * PRODUCTION DEPLOYMENT - BUNDLE PRICING FIX
 * Database: if0_42783325_salestrack2
 * URL: https://salestrack.infinityfreeapp.com/
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'if0_42783325_salestrack2');
define('DB_USER', 'if0_42783325');
define('DB_PASS', 'Patrick121603');

echo "\n=== PRODUCTION DEPLOYMENT VERIFICATION ===\n\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Connected to production database\n";
    echo "   Database: " . DB_NAME . "\n\n";
    
    // Count existing data
    $users = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
    $products = $pdo->query("SELECT COUNT(*) as cnt FROM products")->fetch()['cnt'];
    $sales = $pdo->query("SELECT COUNT(*) as cnt FROM sales")->fetch()['cnt'];
    $saleItems = $pdo->query("SELECT COUNT(*) as cnt FROM sale_items")->fetch()['cnt'];
    
    echo "📊 EXISTING DATA (WILL BE PRESERVED):\n";
    echo "   - Users: $users\n";
    echo "   - Products: $products\n";
    echo "   - Sales: $sales\n";
    echo "   - Sale Items: $saleItems\n\n";
    
    // Check for bundle products
    $bundleProducts = $pdo->query(
        "SELECT COUNT(*) as cnt FROM product_variants WHERE selling_unit = 'bundle'"
    )->fetch()['cnt'];
    
    echo "🎁 BUNDLE PRODUCTS:\n";
    echo "   - Found: $bundleProducts bundle products\n\n";
    
    // Backup location
    $backupDir = __DIR__ . '/production-backups';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
    
    $backupFile = $backupDir . '/backup-' . date('Y-m-d-H-i-s') . '.sql';
    
    // Create backup
    echo "📦 Creating database backup...\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- Production Backup - " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $sql .= "-- TABLE: $table\n";
        $result = $pdo->query("SHOW CREATE TABLE $table")->fetch();
        $sql .= $result['Create Table'] . ";\n\n";
        
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $cols = array_keys($rows[0]);
            $colStr = '`' . implode('`, `', $cols) . '`';
            foreach ($rows as $row) {
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), $row);
                $sql .= "INSERT INTO `$table` ($colStr) VALUES (" . implode(', ', $vals) . ");\n";
            }
        }
    }
    
    file_put_contents($backupFile, $sql);
    echo "   ✅ Backup saved: $backupFile\n";
    echo "   Size: " . round(filesize($backupFile)/1024, 2) . " KB\n\n";
    
    echo "✅ READY FOR DEPLOYMENT\n";
    echo "   All data backed up\n";
    echo "   Safe to deploy bundle fix\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
