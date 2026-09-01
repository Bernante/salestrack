<?php
require 'config/database.php';
$db = getDBConnection();

echo "=== PRODUCT_VARIANTS COLUMNS ===\n";
$cols = $db->query('DESCRIBE product_variants')->fetchAll();
foreach($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . " | Null: " . $c['Null'] . " | Key: " . $c['Key'] . PHP_EOL;
}

echo "\n=== USERS COLUMNS ===\n";
$cols = $db->query('DESCRIBE users')->fetchAll();
foreach($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . PHP_EOL;
}

echo "\n=== SALES COLUMNS ===\n";
$cols = $db->query('DESCRIBE sales')->fetchAll();
foreach($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . PHP_EOL;
}
