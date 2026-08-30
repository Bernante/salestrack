<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/admin-auth.php';

$db = getDBConnection();

$stmtToday = $db->query('SELECT COALESCE(SUM(total_amount), 0) AS total_sales, COUNT(id) AS transaction_count FROM sales WHERE status = "completed" AND DATE(created_at) = CURDATE()');
$todayStat = $stmtToday->fetch();
$todaySales = floatval($todayStat['total_sales']);
$todayTxns = intval($todayStat['transaction_count']);

$stmtVariant = $db->query('
    SELECT p.name AS product_name, pv.variant_name, COALESCE(SUM(si.quantity), 0) as total_qty, COALESCE(SUM(si.subtotal), 0) as total_revenue
    FROM sale_items si JOIN sales s ON si.sale_id = s.id
    JOIN product_variants pv ON si.product_variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    WHERE s.status = "completed" AND DATE(s.created_at) = CURDATE()
    GROUP BY pv.id ORDER BY p.name ASC, pv.variant_name ASC
');
$variantBreakdown = $stmtVariant->fetchAll();

$recentSales = $db->query('
    SELECT s.*, u.name AS staff_name FROM sales s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 6
')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="py-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8 max-w-3xl mx-auto">
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="items-center font-semibold inline-flex text-lg text-brand-700"><span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-peso-sign"></i></span>Today's Sales</h5>
                <i class="fas fa-ellipsis-h text-brand-300"></i>
            </div></div>
            <div class="w-full p-6"><h4 class="font-bold text-brand-500 text-2xl mb-4">₱<?= number_format($todaySales, 2); ?></h4>
                <div class="flex items-center"><span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-green-50 text-green-600 mr-2 text-sm"><i class="fas mr-2 fa-arrow-up"></i><?= $todayTxns; ?></span><p class="font-semibold text-sm text-brand-300">Transactions Today</p></div>
            </div>
        </div>
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="items-center font-semibold inline-flex text-lg text-brand-700"><span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-receipt"></i></span>Transactions</h5>
                <i class="fas fa-ellipsis-h text-brand-300"></i>
            </div></div>
            <div class="w-full p-6"><h4 class="font-bold text-brand-500 text-2xl mb-4"><?= number_format($todayTxns); ?></h4>
                <div class="flex items-center"><span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-green-50 text-green-600 mr-2 text-sm"><i class="fas mr-2 fa-check"></i>completed</span><p class="font-semibold text-sm text-brand-300">Receipts Processed</p></div>
            </div>
        </div>
    </div>


    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4 w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4"><h5 class="font-semibold text-lg text-brand-700">Variant Breakdown</h5><i class="fas fa-ellipsis-h text-brand-300"></i></div></div>
            <div class="w-full p-6 space-y-3">
                <?php if (empty($variantBreakdown)): ?>
                    <p class="text-sm text-brand-300 italic text-center py-4">No variant sales data yet today.</p>
                <?php else: ?>
                    <?php foreach ($variantBreakdown as $vb): ?>
                        <div class="flex items-center justify-between p-3 rounded-md bg-brand-50 border border-brand-200">
                            <div><p class="text-sm font-semibold text-brand-700"><?= e($vb['product_name']); ?> — <?= e($vb['variant_name']); ?></p><p class="text-xs text-brand-300 mt-0.5">₱<?= number_format($vb['total_revenue'], 2); ?> revenue</p></div>
                            <span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-brand-100 text-brand-500 text-sm"><?= number_format($vb['total_qty']); ?> sold</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="lg:col-span-8 w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4"><h5 class="font-semibold text-lg text-brand-700">Recent Transactions</h5><a href="/admin/sales.php" class="text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors">View All &rarr;</a></div></div>
            <div class="w-full p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[500px]">
                        <thead><tr class="border-b border-brand-200"><th class="px-4 py-3 text-sm font-semibold text-brand-300">Txn #</th><th class="px-4 py-3 text-sm font-semibold text-brand-300">Staff</th><th class="px-4 py-3 text-sm font-semibold text-brand-300">Total Amount</th><th class="px-4 py-3 text-sm font-semibold text-brand-300">Time</th><th class="px-4 py-3 text-sm font-semibold text-brand-300 text-right">Receipt</th></tr></thead>
                        <tbody>
                            <?php if (empty($recentSales)): ?>
                                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-brand-300 italic">No sales recorded yet today.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentSales as $rs): ?>
                                    <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                        <td class="px-4 py-3 font-semibold text-brand-700 font-mono text-sm"><?= e($rs['transaction_number']); ?></td>
                                        <td class="px-4 py-3 text-sm text-brand-700"><?= e($rs['staff_name']); ?></td>
                                        <td class="px-4 py-3 font-bold text-brand-500 text-sm">₱<?= number_format($rs['total_amount'], 2); ?></td>
                                        <td class="px-4 py-3 text-sm text-brand-300"><?= date('h:i A', strtotime($rs['created_at'])); ?></td>
                                        <td class="px-4 py-3 text-right"><a href="/admin/sale-details.php?id=<?= $rs['id']; ?>" class="inline-flex items-center px-3 py-1 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">Details</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
