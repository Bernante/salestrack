<?php
require 'config/database.php';
$db = getDBConnection();
$cols = $db->query('DESCRIBE product_variants')->fetchAll();
echo "Current product_variants columns:\n";
foreach($cols as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ')' . PHP_EOL;
}
