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
    SELECT s.id, s.transaction_number, s.user_id, s.created_at, s.total_amount, s.amount_paid, s.change_amount, s.payment_status, s.status, u.name AS staff_name 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC LIMIT 6
')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="py-6 space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 text-white p-4 sm:p-6 md:p-8 rounded-md shadow-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-5">
        <div class="flex-1">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-white text-xs font-semibold mb-2">
                <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span> Terminal Ready
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Welcome back, <?= e($_SESSION['user_name']); ?>! 👋</h1>
            <p class="text-white/80 text-sm mt-1 sm:mt-2">Monitor sales performance, manage your product catalog, and keep your team on track.</p>
        </div>
        <a href="/admin/reports.php" class="inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-3 sm:py-3.5 bg-white text-brand-500 hover:bg-brand-50 font-bold text-sm sm:text-base rounded-md shadow-md transition flex-shrink-0 whitespace-nowrap w-full sm:w-auto">
            <i class="fas fa-chart-line"></i>
            <span>VIEW REPORTS</span>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="items-center font-semibold inline-flex text-lg text-brand-700"><span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-peso-sign"></i></span>Today's Sales</h5>
                <i class="fas fa-ellipsis-h text-brand-300"></i>
            </div></div>
            <div class="w-full p-6"><h4 class="font-bold text-brand-500 text-2xl mb-4">₱<?= number_format($todaySales, 2); ?></h4>
                <div class="flex items-center"><span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-brand-100 text-brand-700 mr-2 text-sm"><i class="fas mr-2 fa-arrow-up"></i><?= $todayTxns; ?></span><p class="font-semibold text-sm text-brand-300">Transactions Today</p></div>
            </div>
        </div>
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="items-center font-semibold inline-flex text-lg text-brand-700"><span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-receipt"></i></span>Transactions</h5>
                <i class="fas fa-ellipsis-h text-brand-300"></i>
            </div></div>
            <div class="w-full p-6"><h4 class="font-bold text-brand-500 text-2xl mb-4"><?= number_format($todayTxns); ?></h4>
                <div class="flex items-center"><span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-brand-100 text-brand-700 mr-2 text-sm"><i class="fas mr-2 fa-check"></i>completed</span><p class="font-semibold text-sm text-brand-300">Receipts Processed</p></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-6">
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
                  <!-- Mobile Card View (hidden on md+) -->
                  <div class="md:hidden space-y-3">
                      <?php if (empty($recentSales)): ?>
                          <div class="text-center text-sm text-brand-300 italic py-6">No sales recorded yet today.</div>
                      <?php else: ?>
                          <?php foreach ($recentSales as $rs): ?>
                              <div class="border border-brand-100 rounded-md p-4 space-y-2 hover:bg-brand-50 transition-colors">
                                  <div class="flex items-center justify-between">
                                      <span class="text-xs font-semibold text-brand-300 uppercase">Txn #</span>
                                      <span class="font-mono font-bold text-brand-700 text-sm"><?= e($rs['transaction_number']); ?></span>
                                  </div>
                                  <div class="flex items-center justify-between">
                                      <span class="text-xs font-semibold text-brand-300 uppercase">Total</span>
                                      <span class="font-bold text-brand-500">₱<?= number_format($rs['total_amount'], 2); ?></span>
                                  </div>
                                  <div class="flex items-center justify-between">
                                      <span class="text-xs font-semibold text-brand-300 uppercase">Status</span>
                                      <span class="px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider <?= $rs['status'] === 'completed' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'; ?>"><?= e(ucfirst($rs['status'])); ?></span>
                                  </div>
                                  <div class="pt-2 border-t border-brand-100">
                                      <a href="/admin/sale-details.php?id=<?= $rs['id']; ?>" class="block px-3 py-2 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors text-center">View Details</a>
                                  </div>
                              </div>
                          <?php endforeach; ?>
                      <?php endif; ?>
                  </div>

                  <!-- Desktop Table View (hidden on mobile) -->
                  <div class="hidden md:block overflow-x-auto">
                      <table class="w-full text-left text-sm">
                    <table class="w-full text-left text-sm ">
                        <thead><tr class="border-b border-brand-200"><th class="px-4 py-3 text-sm font-semibold text-brand-300">Txn #</th><th class="px-4 py-3 text-sm font-semibold text-brand-300">Staff</th><th class="px-4 py-3 text-sm font-semibold text-brand-300">Total Amount</th><th class="px-4 py-3 text-sm font-semibold text-brand-300">Sale Date</th><th class="px-4 py-3 text-sm font-semibold text-brand-300">Status</th><th class="px-4 py-3 text-sm font-semibold text-brand-300 text-right">Receipt</th></tr></thead>
                        <tbody>
                            <?php if (empty($recentSales)): ?>
                                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-brand-300 italic">No sales recorded yet today.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentSales as $rs): ?>
                                    <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                        <td class="px-4 py-3 font-semibold text-brand-700 font-mono text-sm"><?= e($rs['transaction_number']); ?></td>
                                        <td class="px-4 py-3 text-sm text-brand-700"><?= e($rs['staff_name']); ?></td>
                                        <td class="px-4 py-3 font-bold text-brand-500 text-sm">₱<?= number_format($rs['total_amount'], 2); ?></td>
                                        <td class="px-4 py-3 text-sm text-brand-300"><?= date('F d, Y', strtotime($rs['created_at'])); ?></td>
                                        <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider <?= $rs['status'] === 'completed' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'; ?>"><?= e(ucfirst($rs['status'])); ?></span></td>
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
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

