<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/staff-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$userRole = $_SESSION['user_role'] ?? 'staff';
$productsUrl = ($userRole === 'admin') ? '/admin/products.php' : '/staff/products.php';
$createUrl = ($userRole === 'admin') ? '/admin/product-create.php' : '/staff/product-create.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $productsUrl);
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid request token.';
    header('Location: ' . $productsUrl);
    exit;
}

$productName = trim($_POST['name'] ?? '');
$variantNames = $_POST['variant_name'] ?? [];
$sellingUnits = $_POST['selling_unit'] ?? [];
$piecesPerUnit = $_POST['pieces_per_unit'] ?? [];
$variantPrices = $_POST['variant_price'] ?? [];

if (empty($productName)) {
    $_SESSION['flash_error'] = 'Product name is required.';
    header('Location: ' . $createUrl);
    exit;
}

if (empty($variantNames) || !is_array($variantNames)) {
    $_SESSION['flash_error'] = 'At least one variant is required.';
    header('Location: ' . $createUrl);
    exit;
}


$db = getDBConnection();

// Ensure quantity column exists
try {
    $cols = $db->query("SHOW COLUMNS FROM product_variants LIKE 'quantity'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE product_variants ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER variant_name");
    }
} catch (Exception $e) {}

try {
    $imagePath = null;
    if (isset($_FILES['image'])) {
        $imagePath = handleProductImageUpload($_FILES['image']);
    }

    $db->beginTransaction();

    // 1. Insert product with image
    $stmt = $db->prepare('INSERT INTO products (name, image, status) VALUES (:name, :image, "active")');
    $stmt->execute([
        ':name'  => $productName,
        ':image' => $imagePath
    ]);
    $productId = $db->lastInsertId();

    // 2. Insert variants with selling_unit and pieces_per_unit
    $stmtVariant = $db->prepare('INSERT INTO product_variants (product_id, variant_name, quantity, selling_unit, pieces_per_unit, price, status) VALUES (:product_id, :variant_name, :quantity, :selling_unit, :pieces_per_unit, :price, "active")');

    foreach ($variantNames as $index => $vName) {
        $vNameClean = trim($vName);
        if (empty($vNameClean)) {
            continue;
        }
        $vUnit = trim($sellingUnits[$index] ?? 'piece');
        $vPieces = max(1, intval($piecesPerUnit[$index] ?? 1));
        $vPrice = floatval($variantPrices[$index] ?? 0);
        if ($vPrice < 0) {
            $vPrice = 0.00;
        }

        $stmtVariant->execute([
            ':product_id'      => $productId,
            ':variant_name'    => $vNameClean,
            ':quantity'        => $vPieces,
            ':selling_unit'    => $vUnit,
            ':pieces_per_unit' => $vPieces,
            ':price'           => $vPrice
        ]);
    }

    $db->commit();
    $_SESSION['flash_success'] = 'Product and variants saved successfully!';
    header('Location: ' . $productsUrl);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Save Product Error: ' . $e->getMessage());
    $_SESSION['flash_error'] = $e->getMessage() ?: 'Failed to create product. Please try again.';
    header('Location: ' . $createUrl);
    exit;
}
