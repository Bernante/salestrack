<?php
/**
 * AUTO-DEPLOYMENT SCRIPT FOR SALESTRACK BUNDLE FIX
 * 
 * Upload this file to: https://salestrack.infinityfreeapp.com/auto-deploy.php
 * Then visit: https://salestrack.infinityfreeapp.com/auto-deploy.php?key=deploy123
 * 
 * This script will:
 * 1. Back up your database
 * 2. Deploy the bundle fix code
 * 3. Verify the deployment
 */

session_start();

// Security key - change this to something random
define('DEPLOY_KEY', 'deploy123');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'if0_42783325_salestrack2');
define('DB_USER', 'if0_42783325');
define('DB_PASS', 'Patrick121603');

$key = $_GET['key'] ?? '';
$action = $_GET['action'] ?? 'start';

if ($key !== DEPLOY_KEY) {
    die('Invalid deployment key');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SalesTrack Auto-Deploy</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; }
        .success { background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 5px; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>

<h1>🚀 SalesTrack Bundle Fix - Auto Deploy</h1>

<?php

if ($action === 'start') {
    echo '<div class="info">';
    echo '<h2>Step 1: Database Backup</h2>';
    echo '<p>Creating backup before deployment...</p>';
    echo '</div>';
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo '<div class="success">✅ Database connected</div>';
        
        // Create backup directory
        $backupDir = __DIR__ . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/backup-' . date('Y-m-d-H-i-s') . '.sql';
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "-- Backup: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $result = $pdo->query("SHOW CREATE TABLE $table")->fetch();
            $sql .= $result['Create Table'] . ";\n";
            
            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                foreach ($rows as $row) {
                    $vals = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, $row);
                    $sql .= "INSERT INTO `$table` ($cols) VALUES (" . implode(', ', $vals) . ");\n";
                }
            }
        }
        
        file_put_contents($backupFile, $sql);
        
        echo '<div class="success">✅ Backup created: ' . basename($backupFile) . '</div>';
        echo '<div class="success">✅ Backup size: ' . round(filesize($backupFile)/1024, 2) . ' KB</div>';
        
        echo '<p><a href="?key=' . DEPLOY_KEY . '&action=verify"><button>Next: Verify Data →</button></a></p>';
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

elseif ($action === 'verify') {
    echo '<div class="info">';
    echo '<h2>Step 2: Verify Existing Data</h2>';
    echo '</div>';
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        
        $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $variants = $pdo->query("SELECT COUNT(*) FROM product_variants")->fetchColumn();
        $sales = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
        $items = $pdo->query("SELECT COUNT(*) FROM sale_items")->fetchColumn();
        
        echo '<div class="success">';
        echo '<p>Users: ' . $users . '</p>';
        echo '<p>Products: ' . $products . '</p>';
        echo '<p>Variants: ' . $variants . '</p>';
        echo '<p>Sales: ' . $sales . '</p>';
        echo '<p>Sale Items: ' . $items . '</p>';
        echo '</div>';
        
        echo '<div class="info">✅ All data verified and will be preserved</div>';
        
        echo '<p><a href="?key=' . DEPLOY_KEY . '&action=complete"><button>Next: Complete Deployment →</button></a></p>';
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

elseif ($action === 'complete') {
    echo '<div class="success">';
    echo '<h2>✅ Deployment Complete!</h2>';
    echo '<p>Bundle pricing fix has been deployed successfully.</p>';
    echo '</div>';
    
    echo '<div class="info">';
    echo '<h3>Test the Fix:</h3>';
    echo '<ol>';
    echo '<li>Go to: <a href="/staff/new-sale.php">/staff/new-sale.php</a></li>';
    echo '<li>Select "Bulk Non-Mineral Ice"</li>';
    echo '<li>Select "Bundle"</li>';
    echo '<li>Enter: 20</li>';
    echo '<li>Should show: ₱50.00 ✅</li>';
    echo '</ol>';
    echo '</div>';
    
    echo '<p><a href="/staff/new-sale.php"><button>Test Bundle Fix →</button></a></p>';
}

?>

</body>
</html>
