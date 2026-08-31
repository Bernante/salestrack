<?php
/**
 * SalesTrack - System Verification Script
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config/database.php';

$tests = [];
$testsPassed = 0;
$testsFailed = 0;

function addTest($name, $result, $message = '') {
    global $tests, $testsPassed, $testsFailed;
    if ($result) $testsPassed++;
    else $testsFailed++;
    $tests[] = ['name' => $name, 'status' => $result ? 'PASS' : 'FAIL', 'message' => $message];
}

try {
    $db = getDBConnection();
    addTest('Database Connection', true, 'Connected');
    
    $result = $db->query('SHOW TABLES LIKE "products"')->fetch();
    addTest('Products Table', $result !== false, 'Table exists');
    
    $result = $db->query('SHOW TABLES LIKE "product_variants"')->fetch();
    addTest('Product Variants Table', $result !== false, 'Table exists');
    
    $columns = $db->query("SHOW COLUMNS FROM product_variants LIKE 'quantity'")->fetchAll();
    $hasQty = !empty($columns);
    addTest('Quantity Column', $hasQty, $hasQty ? 'Exists' : 'Missing');
    
    if (!$hasQty) {
        $db->exec("ALTER TABLE product_variants ADD COLUMN quantity INT NOT NULL DEFAULT 1");
        addTest('Quantity Column Auto-Added', true, 'Fixed');
    }
    
    $columns = $db->query("SHOW COLUMNS FROM product_variants LIKE 'item_quantity'")->fetchAll();
    if (!empty($columns)) {
        $db->exec("ALTER TABLE product_variants DROP COLUMN item_quantity");
        addTest('Legacy Column Removed', true, 'Cleaned');
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SalesTrack - System Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-600 to-pink-600 min-h-screen p-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">🔍 System Verification</h1>
            
            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="bg-blue-50 p-4 rounded text-center">
                    <div class="text-2xl font-bold text-blue-600"><?= count($tests); ?></div>
                    <div class="text-xs text-gray-600">Total Tests</div>
                </div>
                <div class="bg-green-50 p-4 rounded text-center">
                    <div class="text-2xl font-bold text-green-600"><?= $testsPassed; ?></div>
                    <div class="text-xs text-gray-600">Passed</div>
                </div>
                <div class="bg-red-50 p-4 rounded text-center">
                    <div class="text-2xl font-bold text-red-600"><?= $testsFailed; ?></div>
                    <div class="text-xs text-gray-600">Failed</div>
                </div>
            </div>

            <div class="space-y-2">
                <?php foreach ($tests as $test): ?>
                    <div class="border-2 p-3 rounded <?= $test['status'] === 'PASS' ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50'; ?>">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-800"><?= $test['name']; ?></span>
                            <span class="<?= $test['status'] === 'PASS' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                <?= $test['status'] === 'PASS' ? '✓' : '✗'; ?> <?= $test['status']; ?>
                            </span>
                        </div>
                        <?php if ($test['message']): ?>
                            <div class="text-xs text-gray-600 mt-1"><?= $test['message']; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 p-4 bg-gray-100 rounded text-center">
                <?php if ($testsFailed === 0): ?>
                    <div class="text-green-600 font-bold mb-3">✓ All Systems Operational!</div>
                    <p class="text-sm text-gray-700 mb-4">The database and schema are ready.</p>
                <?php else: ?>
                    <div class="text-red-600 font-bold mb-3">⚠ <?= $testsFailed; ?> Issue(s) Detected</div>
                    <p class="text-sm text-gray-700 mb-4">Auto-fixes have been applied. Check details above.</p>
                <?php endif; ?>
                
                <div class="space-y-2">
                    <a href="/admin/products.php" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Admin Products</a>
                    <a href="/staff/products.php" class="inline-block px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm ml-2">Staff Products</a>
                    <a href="/staff/new-sale.php" class="inline-block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm ml-2">New Sale</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php

    
    $result = $db->query('SELECT COUNT(*) as cnt FROM products')->fetch();
    addTest('Sample Products', ($result['cnt'] ?? 0) > 0, 'Found ' . ($result['cnt'] ?? 0));
    
    addTest('getProductImage Function', function_exists('getProductImage'), 'Exists');
    addTest('handleProductImageUpload Function', function_exists('handleProductImageUpload'), 'Exists');
    
} catch (Exception $e) {
    addTest('Error', false, $e->getMessage());
}
