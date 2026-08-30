<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireLogin();

$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/staff/sales.php';

// Strict backend role enforcement: Only Staff can cancel orders
if (($_SESSION['user_role'] ?? '') !== 'staff') {
    http_response_code(403);
    $_SESSION['flash_error'] = 'Access Denied: Admins are not authorized to cancel orders. Only Staff can cancel orders.';
    header('Location: /admin/sales.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid security request token.';
    header('Location: ' . $redirectUrl);
    exit;
}

$saleId = intval($_POST['sale_id'] ?? 0);

if ($saleId <= 0) {
    $_SESSION['flash_error'] = 'Invalid transaction ID.';
    header('Location: ' . $redirectUrl);
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
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Staff ownership check: Staff can only cancel transactions recorded by themselves
    if ($sale['user_id'] != $_SESSION['user_id']) {
        $_SESSION['flash_error'] = 'Unauthorized access: You can only cancel transactions recorded by your account.';
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($sale['status'] === 'cancelled') {
        $_SESSION['flash_error'] = 'This transaction is already cancelled.';
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($sale['status'] !== 'completed') {
        $_SESSION['flash_error'] = 'Transaction is not eligible for cancellation.';
        header('Location: ' . $redirectUrl);
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
    header('Location: ' . $redirectUrl);
    exit;

} catch (Exception $e) {
    error_log('Cancel Sale Error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Failed to cancel sale: ' . $e->getMessage();
    header('Location: ' . $redirectUrl);
    exit;
}



