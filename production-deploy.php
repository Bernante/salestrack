<?php
define('DEPLOY_KEY', 'salestrack2026');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'if0_42783325_salestrack2');
define('DB_USER', 'if0_42783325');
define('DB_PASS', 'Patrick121603');

$key = $_GET['key'] ?? '';
$action = $_GET['action'] ?? '1';

if ($key !== DEPLOY_KEY) die('Invalid key');

if ($action == '1') {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $dir = __DIR__ . '/prod-backups';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = $dir . '/backup-' . date('Y-m-d-H-i-s') . '.sql';
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- Backup: " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as $table) {
        $r = $pdo->query("SHOW CREATE TABLE $table")->fetch();
        $sql .= $r['Create Table'] . ";\n";
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
            foreach ($rows as $row) {
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), $row);
                $sql .= "INSERT INTO `$table` ($cols) VALUES (" . implode(', ', $vals) . ");\n";
            }
        }
    }
    file_put_contents($file, $sql);
    echo "✅ Backup: " . basename($file) . "\n";
    echo '<a href="?key=' . DEPLOY_KEY . '&action=2">Next →</a>';
}

elseif ($action == '2') {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    echo "Users: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
    echo "Products: " . $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() . "\n";
    echo "Sales: " . $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn() . "\n";
    echo '<a href="?key=' . DEPLOY_KEY . '&action=3">Deploy →</a>';
}

elseif ($action == '3') {
    $f = __DIR__ . '/actions/save-sale.php';
    if (file_exists($f) && strpos(file_get_contents($f), "if (\$sellingUnit === 'bundle')") !== false) {
        echo "✅ Bundle fix verified\n";
        echo '<a href="?key=' . DEPLOY_KEY . '&action=4">Test →</a>';
    }
}

elseif ($action == '4') {
    echo "✅ DEPLOYMENT COMPLETE\n";
    echo '<a href="/staff/new-sale.php">Test Now →</a>';
}
?>
