<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/products.php');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid request token.';
    header('Location: /admin/products.php');
    exit;
}

$productName = trim($_POST['name'] ?? '');
$variantNames = $_POST['variant_name'] ?? [];
$variantPrices = $_POST['variant_price'] ?? [];

if (empty($productName)) {
    $_SESSION['flash_error'] = 'Product name is required.';
    header('Location: /admin/product-create.php');
    exit;
}

if (empty($variantNames) || !is_array($variantNames)) {
    $_SESSION['flash_error'] = 'At least one variant is required.';
    header('Location: /admin/product-create.php');
    exit;
}

$db = getDBConnection();

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

    // 2. Insert variants
    $stmtVariant = $db->prepare('INSERT INTO product_variants (product_id, variant_name, price, status) VALUES (:product_id, :variant_name, :price, "active")');

    foreach ($variantNames as $index => $vName) {
        $vNameClean = trim($vName);
        if (empty($vNameClean)) {
            continue; // Skip blank inputs
        }
        $vPrice = floatval($variantPrices[$index] ?? 0);
        if ($vPrice < 0) {
            $vPrice = 0.00;
        }

        $stmtVariant->execute([
            ':product_id'   => $productId,
            ':variant_name' => $vNameClean,
            ':price'        => $vPrice
        ]);
    }

    $db->commit();
    $_SESSION['flash_success'] = 'Product and variants saved successfully!';
    header('Location: /admin/products.php');
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Save Product Error: ' . $e->getMessage());
    $_SESSION['flash_error'] = $e->getMessage() ?: 'Failed to create product. Please try again.';
    header('Location: /admin/product-create.php');
    exit;
}
