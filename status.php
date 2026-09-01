<?php
require 'config/database.php';
$db = getDBConnection();

echo "═══════════════════════════════════════════════════════════\n";
echo "DATABASE STATUS CHECK\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Check users
$users = $db->query("SELECT COUNT(*) as cnt FROM users")->fetch();
echo "✓ Users: " . $users['cnt'] . " accounts\n";

$userList = $db->query("SELECT id, name, username, role FROM users")->fetchAll();
foreach ($userList as $u) {
    echo "  • " . $u['name'] . " (" . $u['username'] . ") - " . $u['role'] . "\n";
}

echo "\n";

// Check products
$products = $db->query("SELECT COUNT(*) as cnt FROM products")->fetch();
echo "✓ Products: " . $products['cnt'] . " products\n";

$productList = $db->query("SELECT id, name, status FROM products")->fetchAll();
foreach ($productList as $p) {
    echo "  • " . $p['name'] . " (status: " . $p['status'] . ")\n";
}

echo "\n";

// Check variants
$variants = $db->query("SELECT COUNT(*) as cnt FROM product_variants")->fetch();
echo "✓ Variants: " . $variants['cnt'] . " variants\n";

$variantList = $db->query("
    SELECT pv.id, p.name as product, pv.variant_name, pv.selling_unit, pv.pieces_per_unit, pv.price 
    FROM product_variants pv 
    JOIN products p ON pv.product_id = p.id 
    ORDER BY p.name, pv.id
")->fetchAll();

foreach ($variantList as $v) {
    echo "  • " . $v['product'] . " - " . $v['variant_name'];
    echo " (" . $v['selling_unit'];
    if ($v['pieces_per_unit'] > 1) {
        echo ", " . $v['pieces_per_unit'] . " per unit";
    }
    echo ") ₱" . number_format($v['price'], 2) . "\n";
}

echo "\n";

// Check sales
$sales = $db->query("SELECT COUNT(*) as cnt FROM sales")->fetch();
echo "✓ Sales: " . $sales['cnt'] . " transactions\n";

echo "\n═══════════════════════════════════════════════════════════\n";
echo "✓✓✓ DATABASE READY FOR USE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Visit: http://localhost:8000/login.php\n";
echo "Login: admin / admin123\n";
