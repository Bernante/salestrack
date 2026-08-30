<?php
$pageTitle = 'Transaction Details';
require_once __DIR__ . '/../includes/admin-auth.php';

$saleId = intval($_GET['id'] ?? 0);
if ($saleId <= 0) {
    header('Location: /admin/sales.php');
    exit;
}

$db = getDBConnection();

$stmt = $db->prepare('
    SELECT s.*, u.name AS staff_name
    FROM sales s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = :id
');
$stmt->execute([':id' => $saleId]);
$sale = $stmt->fetch();

if (!$sale) {
    $_SESSION['flash_error'] = 'Sale transaction not found.';
    header('Location: /admin/sales.php');
    exit;
}

$stmtItems = $db->prepare('
    SELECT si.*, p.name AS product_name, pv.variant_name
    FROM sale_items si
    JOIN product_variants pv ON si.product_variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    WHERE si.sale_id = :sale_id
    ORDER BY si.id ASC
');
$stmtItems->execute([':sale_id' => $saleId]);
$items = $stmtItems->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-brand-300 uppercase tracking-wider block">Official Receipt</span>
            <h1 class="text-2xl font-bold text-brand-700 font-mono">Txn #<?= e($sale['transaction_number']); ?></h1>
        </div>
        <a href="/admin/sales.php" class="inline-flex items-center gap-1 px-4 py-2 bg-brand-100 hover:bg-brand-200 text-brand-500 font-semibold text-sm rounded-sm transition-colors">
            &larr; Back to Sales History
        </a>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-brand-50 p-5 rounded-md border border-brand-200 text-sm">
            <div>
                <span class="block text-xs font-semibold text-brand-300 uppercase tracking-wider">Staff Member</span>
                <span class="font-bold text-brand-700 text-sm"><?= e($sale['staff_name']); ?></span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-brand-300 uppercase tracking-wider">Date & Time</span>
                <span class="font-semibold text-brand-700 text-sm"><?= date('M d, Y h:i A', strtotime($sale['created_at'])); ?></span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-brand-300 uppercase tracking-wider">Payment Status</span>
                <span class="font-bold text-brand-700 text-sm uppercase"><?= e($sale['payment_status']); ?></span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-brand-300 uppercase tracking-wider">Status</span>
                <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider <?= $sale['status'] === 'completed' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'; ?>">
                    <?= e(ucfirst($sale['status'])); ?>
                </span>
            </div>
        </div>

        <?php if ($sale['status'] === 'cancelled'): ?>
            <div class="bg-red-50 p-4 rounded-md border border-red-200 text-sm space-y-1">
                <span class="font-bold text-red-700 uppercase tracking-wider block">Order Cancelled Record</span>
                <p class="text-red-600 font-medium">This transaction has been marked as cancelled.</p>
            </div>
        <?php endif; ?>

        <div>
            <h3 class="text-sm font-semibold text-brand-700 uppercase tracking-wider mb-3">Purchased Items Breakdown</h3>
            <div class="overflow-x-auto border border-brand-200 rounded-md">
                <table class="w-full text-left text-sm min-w-[500px]">
                    <thead>
                        <tr class="border-b border-brand-200">
                            <th class="px-5 py-3 text-sm font-semibold text-brand-300">Product</th>
                            <th class="px-5 py-3 text-sm font-semibold text-brand-300">Variant</th>
                            <th class="px-5 py-3 text-sm font-semibold text-brand-300">Qty</th>
                            <th class="px-5 py-3 text-sm font-semibold text-brand-300">Unit Price</th>
                            <th class="px-5 py-3 text-sm font-semibold text-brand-300 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                <td class="px-5 py-3.5 font-bold text-brand-700 text-sm"><?= e($item['product_name']); ?></td>
                                <td class="px-5 py-3.5 text-brand-700 text-sm"><?= e($item['variant_name']); ?></td>
                                <td class="px-5 py-3.5 font-semibold text-brand-700 text-sm"><?= $item['quantity']; ?></td>
                                <td class="px-5 py-3.5 text-brand-700 text-sm">₱<?= number_format($item['unit_price'], 2); ?></td>
                                <td class="px-5 py-3.5 text-right font-bold text-brand-500 text-sm">₱<?= number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="border-t border-brand-200 pt-5 space-y-2 text-right">
            <div class="text-sm text-brand-300 font-semibold">Total Amount: <span class="text-xl font-bold text-brand-500 ml-2">₱<?= number_format($sale['total_amount'], 2); ?></span></div>
            <div class="text-sm text-brand-300 font-semibold">Amount Paid: <span class="text-brand-700 ml-2">₱<?= number_format($sale['amount_paid'], 2); ?></span></div>
            <div class="text-sm text-brand-300 font-semibold">Change Given: <span class="text-brand-700 ml-2">₱<?= number_format($sale['change_amount'], 2); ?></span></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
