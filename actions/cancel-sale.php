<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireLogin();

// Strict backend role enforcement: Only Staff can cancel orders
if (($_SESSION['user_role'] ?? '') !== 'staff') {
    $_SESSION['flash_error'] = 'Access Denied: Only Staff can cancel orders.';
    header('Location: /staff/sales.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method.';
    header('Location: /staff/sales.php');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid security request token.';
    header('Location: /staff/sales.php');
    exit;
}

$saleId = intval($_POST['sale_id'] ?? 0);

if ($saleId <= 0) {
    $_SESSION['flash_error'] = 'Invalid transaction ID.';
    header('Location: /staff/sales.php');
    exit;
}

$db = getDBConnection();

try {
    // Fetch sale record
    $stmt = $db->prepare('SELECT id, transaction_number, user_id, status FROM sales WHERE id = :id');
    $stmt->execute([':id' => $saleId]);
    $sale = $stmt->fetch();

    if (!$sale) {
        $_SESSION['flash_error'] = 'Transaction not found.';
        header('Location: /staff/sales.php');
        exit;
    }

    // Staff ownership check: Staff can only cancel transactions recorded by themselves
    if ($sale['user_id'] != $_SESSION['user_id']) {
        $_SESSION['flash_error'] = 'You can only cancel transactions recorded by your account.';
        header('Location: /staff/sales.php');
        exit;
    }

    if ($sale['status'] === 'cancelled') {
        $_SESSION['flash_error'] = 'This transaction is already cancelled.';
        header('Location: /staff/sale-details.php?id=' . $saleId);
        exit;
    }

    if ($sale['status'] !== 'completed') {
        $_SESSION['flash_error'] = 'Transaction is not eligible for cancellation.';
        header('Location: /staff/sale-details.php?id=' . $saleId);
        exit;
    }

    // Update status to cancelled without deleting
    $updateStmt = $db->prepare('
        UPDATE sales
        SET status = "cancelled",
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id AND status = "completed"
    ');
    $updateStmt->execute([
        ':id'     => $saleId
    ]);

    $_SESSION['flash_success'] = 'Transaction ' . $sale['transaction_number'] . ' was successfully cancelled.';
    header('Location: /staff/sale-details.php?id=' . $saleId);
    exit;

} catch (Exception $e) {
    error_log('Cancel Sale Error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Failed to cancel sale: ' . $e->getMessage();
    header('Location: /staff/sale-details.php?id=' . $saleId);
    exit;
}
