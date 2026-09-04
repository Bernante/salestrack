<?php
require_once 'config/database.php';
$db = getDBConnection();

echo "product_variants table structure:\n";
echo str_repeat("-", 60)."\n";
$cols = $db->query('DESCRIBE product_variants')->fetchAll();
foreach($cols as $c) {
    echo $c['Field'] . " - " . $c['Type'] . " (Null: " . $c['Null'] . ")\n";
}

echo "\n\nproducts table structure:\n";
echo str_repeat("-", 60)."\n";
$cols = $db->query('DESCRIBE products')->fetchAll();
foreach($cols as $c) {
    echo $c['Field'] . " - " . $c['Type'] . " (Null: " . $c['Null'] . ")\n";
}
