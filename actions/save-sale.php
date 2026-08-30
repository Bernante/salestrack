<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/staff-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /staff/new-sale.php');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid request token. Please try again.';
    header('Location: /staff/new-sale.php');
    exit;
}

$rawCartItems = $_POST['cart_items'] ?? '';
$amountPaidInput = floatval($_POST['amount_paid'] ?? 0);
$userId = $_SESSION['user_id'];

$cartItems = json_decode($rawCartItems, true);

if (!is_array($cartItems) || empty($cartItems)) {
    $_SESSION['flash_error'] = 'Your cart is empty. Please add items before completing the sale.';
    header('Location: /staff/new-sale.php');
    exit;
}

$db = getDBConnection();

try {
    // 1. Fetch current prices for all variants directly from MySQL
    $variantIds = array_map(fn($item) => intval($item['product_variant_id'] ?? 0), $cartItems);
    $variantIds = array_filter($variantIds, fn($id) => $id > 0);

    if (empty($variantIds)) {
        $_SESSION['flash_error'] = 'Invalid products selected.';
        header('Location: /staff/new-sale.php');
        exit;
    }

    $inClause = implode(',', array_fill(0, count($variantIds), '?'));
    $stmtPv = $db->prepare("
        SELECT pv.id, pv.product_id, pv.variant_name, pv.price, pv.status AS variant_status,
               p.name AS product_name, p.status AS product_status
        FROM product_variants pv
        JOIN products p ON pv.product_id = p.id
        WHERE pv.id IN ($inClause)
    ");
    $stmtPv->execute(array_values($variantIds));
    $dbVariants = [];
    foreach ($stmtPv->fetchAll() as $row) {
        $dbVariants[$row['id']] = $row;
    }

    // 2. Server-side calculation & validation
    $calculatedItems = [];
    $totalAmount = 0.00;

    foreach ($cartItems as $item) {
        $vid = intval($item['product_variant_id'] ?? 0);
        $qty = intval($item['quantity'] ?? 0);

        if ($qty <= 0) {
            $_SESSION['flash_error'] = 'Quantity must be greater than zero.';
            header('Location: /staff/new-sale.php');
            exit;
        }

        if (!isset($dbVariants[$vid])) {
            $_SESSION['flash_error'] = 'One or more selected products are no longer available.';
            header('Location: /staff/new-sale.php');
            exit;
        }

        $vData = $dbVariants[$vid];
        if ($vData['variant_status'] !== 'active' || $vData['product_status'] !== 'active') {
            $_SESSION['flash_error'] = 'Product "' . e($vData['product_name']) . ' - ' . e($vData['variant_name']) . '" is inactive.';
            header('Location: /staff/new-sale.php');
            exit;
        }

        $unitPrice = floatval($vData['price']);
        $subtotal = round($unitPrice * $qty, 2);
        $totalAmount += $subtotal;

        $calculatedItems[] = [
            'product_variant_id' => $vid,
            'product_name'       => $vData['product_name'],
            'variant_name'       => $vData['variant_name'],
            'quantity'           => $qty,
            'unit_price'         => $unitPrice,
            'subtotal'           => $subtotal
        ];
    }

    $totalAmount = round($totalAmount, 2);

    // 3. Payment Validation
    if ($amountPaidInput < $totalAmount) {
        $_SESSION['flash_error'] = 'Amount paid (₱' . number_format($amountPaidInput, 2) . ') is insufficient. Total is ₱' . number_format($totalAmount, 2) . '.';
        header('Location: /staff/new-sale.php');
        exit;
    }

    $changeAmount = round($amountPaidInput - $totalAmount, 2);

    // 4. Generate Unique Transaction Number
    $transactionNumber = 'SALE-' . str_pad((string)mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

    // 5. Begin DB Transaction
    $db->beginTransaction();

    $stmtSale = $db->prepare('
        INSERT INTO sales (transaction_number, user_id, total_amount, amount_paid, change_amount, payment_status, status)
        VALUES (:txn, :user_id, :total, :paid, :change, "paid", "completed")
    ');
    $stmtSale->execute([
        ':txn'     => $transactionNumber,
        ':user_id' => $userId,
        ':total'   => $totalAmount,
        ':paid'    => $amountPaidInput,
        ':change'  => $changeAmount
    ]);
    $saleId = $db->lastInsertId();

    $stmtItem = $db->prepare('
        INSERT INTO sale_items (sale_id, product_variant_id, quantity, unit_price, subtotal)
        VALUES (:sale_id, :vid, :qty, :price, :subtotal)
    ');

    foreach ($calculatedItems as $cItem) {
        $stmtItem->execute([
            ':sale_id'  => $saleId,
            ':vid'      => $cItem['product_variant_id'],
            ':qty'      => $cItem['quantity'],
            ':price'    => $cItem['unit_price'],
            ':subtotal' => $cItem['subtotal']
        ]);
    }

    $db->commit();

    // Store completed transaction summary in session for success message view
    $_SESSION['completed_sale'] = [
        'sale_id'            => $saleId,
        'transaction_number' => $transactionNumber,
        'total_amount'       => $totalAmount,
        'amount_paid'        => $amountPaidInput,
        'change_amount'      => $changeAmount,
        'items'              => $calculatedItems
    ];

    header('Location: /staff/new-sale.php');
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Save Sale Exception: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Unable to complete the sale. Please try again.';
    header('Location: /staff/new-sale.php');
    exit;
}
