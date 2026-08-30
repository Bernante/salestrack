<?php
$pageTitle = 'Sales Reports';
require_once __DIR__ . '/../includes/admin-auth.php';

$db = getDBConnection();
$period = $_GET['period'] ?? 'today';
$startDateInput = $_GET['start_date'] ?? '';
$endDateInput = $_GET['end_date'] ?? '';

$where = ['s.status = "completed"'];
$params = [];

if ($period === 'today') {
    $where[] = 'DATE(s.created_at) = CURDATE()';
} elseif ($period === 'yesterday') {
    $where[] = 'DATE(s.created_at) = SUBDATE(CURDATE(), 1)';
} elseif ($period === 'week') {
    $where[] = 'YEARWEEK(s.created_at, 1) = YEARWEEK(CURDATE(), 1)';
} elseif ($period === 'month') {
    $where[] = 'YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())';
} elseif ($period === 'custom' && !empty($startDateInput) && !empty($endDateInput)) {
    $where[] = 'DATE(s.created_at) BETWEEN :start_date AND :end_date';
    $params[':start_date'] = $startDateInput;
    $params[':end_date'] = $endDateInput;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmtTotals = $db->prepare("SELECT COALESCE(SUM(s.total_amount), 0) AS total_sales, COUNT(DISTINCT s.id) AS total_transactions FROM sales s $whereSql");
$stmtTotals->execute($params);
$totals = $stmtTotals->fetch();
$totalSales = floatval($totals['total_sales']);
$totalTxns = intval($totals['total_transactions']);

$stmtProdQty = $db->prepare("
    SELECT p.name AS product_name, COALESCE(SUM(si.quantity), 0) AS total_qty
    FROM sale_items si JOIN sales s ON si.sale_id = s.id
    JOIN product_variants pv ON si.product_variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    $whereSql GROUP BY p.name
");
$stmtProdQty->execute($params);
$totalEggs = 0; $totalIce = 0;
foreach ($stmtProdQty->fetchAll() as $pq) {
    if (strcasecmp($pq['product_name'], 'Egg') === 0) $totalEggs = intval($pq['total_qty']);
    if (strcasecmp($pq['product_name'], 'Ice') === 0) $totalIce = intval($pq['total_qty']);
}

$stmtVar = $db->prepare("
    SELECT p.name AS product_name, pv.variant_name, COALESCE(SUM(si.quantity), 0) AS total_qty, COALESCE(SUM(si.subtotal), 0) AS total_revenue
    FROM sale_items si JOIN sales s ON si.sale_id = s.id
    JOIN product_variants pv ON si.product_variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    $whereSql GROUP BY pv.id ORDER BY p.name ASC, pv.variant_name ASC
");
$stmtVar->execute($params);
$variantBreakdown = $stmtVar->fetchAll();

$smallEggs = 0; $mediumEggs = 0; $largeEggs = 0;
foreach ($variantBreakdown as $vb) {
    if (strcasecmp($vb['product_name'], 'Egg') === 0) {
        if (strcasecmp($vb['variant_name'], 'Small') === 0) $smallEggs = intval($vb['total_qty']);
        if (strcasecmp($vb['variant_name'], 'Medium') === 0) $mediumEggs = intval($vb['total_qty']);
        if (strcasecmp($vb['variant_name'], 'Large') === 0) $largeEggs = intval($vb['total_qty']);
    }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="py-6 space-y-6">
    <!-- Header Banner & Filter bar -->
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-brand-200 pb-4">
            <div>
                <h1 class="text-xl font-bold text-brand-700">Sales & Inventory Reports</h1>
                <p class="text-sm text-brand-300">Filter business performance by date ranges and period presets.</p>
            </div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-brand-100 text-brand-500 font-semibold text-xs">
                <span>Period: <strong class="uppercase font-bold text-brand-700"><?= e($period); ?></strong></span>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 sm:gap-3 text-sm">
            <a href="/admin/reports.php?period=today" class="px-4 py-2 rounded-md font-semibold transition-colors <?= $period === 'today' ? 'bg-brand-500 text-white' : 'bg-brand-100 text-brand-500 hover:bg-brand-200'; ?>">Today</a>
            <a href="/admin/reports.php?period=yesterday" class="px-4 py-2 rounded-md font-semibold transition-colors <?= $period === 'yesterday' ? 'bg-brand-500 text-white' : 'bg-brand-100 text-brand-500 hover:bg-brand-200'; ?>">Yesterday</a>
            <a href="/admin/reports.php?period=week" class="px-4 py-2 rounded-md font-semibold transition-colors <?= $period === 'week' ? 'bg-brand-500 text-white' : 'bg-brand-100 text-brand-500 hover:bg-brand-200'; ?>">This Week</a>
            <a href="/admin/reports.php?period=month" class="px-4 py-2 rounded-md font-semibold transition-colors <?= $period === 'month' ? 'bg-brand-500 text-white' : 'bg-brand-100 text-brand-500 hover:bg-brand-200'; ?>">This Month</a>

            <div class="flex items-center gap-2 border-l border-brand-200 pl-3 ml-1 flex-wrap">
                <input type="hidden" name="period" value="custom">
                <input type="date" name="start_date" value="<?= e($startDateInput); ?>" class="px-3 py-1.5 border border-brand-200 rounded-md text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                <span class="text-brand-300 font-semibold">to</span>
                <input type="date" name="end_date" value="<?= e($endDateInput); ?>" class="px-3 py-1.5 border border-brand-200 rounded-md text-sm text-brand-700 focus:outline-none focus:border-brand-500">
                <button type="submit" class="px-4 py-1.5 bg-brand-500 hover:bg-brand-600 text-white rounded-md font-semibold shadow-sm transition-colors">Apply Range</button>
            </div>
        </form>
    </div>

    <!-- Summary Metrics (4 DataAI stat cards) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 xl:grid-cols-4">
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="items-center font-semibold inline-flex text-lg text-brand-700"><span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-peso-sign"></i></span>Total Revenue</h5>
                <i class="fas fa-ellipsis-h text-brand-300"></i>
            </div></div>
            <div class="w-full p-6"><h4 class="font-bold text-brand-500 text-2xl mb-4">₱<?= number_format($totalSales, 2); ?></h4>
                <div class="flex items-center"><span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-green-50 text-green-600 mr-2 text-sm"><i class="fas mr-2 fa-chart-line"></i>Total</span><p class="font-semibold text-sm text-brand-300">Gross revenue</p></div>
            </div>
        </div>
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="items-center font-semibold inline-flex text-lg text-brand-700"><span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-receipt"></i></span>Transactions</h5>
                <i class="fas fa-ellipsis-h text-brand-300"></i>
            </div></div>
            <div class="w-full p-6"><h4 class="font-bold text-brand-500 text-2xl mb-4"><?= number_format($totalTxns); ?></h4>
                <div class="flex items-center"><span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-green-50 text-green-600 mr-2 text-sm"><i class="fas mr-2 fa-check"></i>Completed</span><p class="font-semibold text-sm text-brand-300">Receipts processed</p></div>
            </div>
        </div>
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="items-center font-semibold inline-flex text-lg text-brand-700"><span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-egg"></i></span>Eggs Dispatched</h5>
                <i class="fas fa-ellipsis-h text-brand-300"></i>
            </div></div>
            <div class="w-full p-6"><h4 class="font-bold text-brand-500 text-2xl mb-4"><?= number_format($totalEggs); ?></h4>
                <div class="flex items-center"><span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-green-50 text-green-600 mr-2 text-sm"><i class="fas mr-2 fa-arrow-up"></i>Total</span><p class="font-semibold text-sm text-brand-300">S: <?= $smallEggs; ?> | M: <?= $mediumEggs; ?> | L: <?= $largeEggs; ?></p></div>
            </div>
        </div>
        <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
            <div class="w-full p-6 pb-0"><div class="flex items-center justify-between border-b border-brand-200 pb-4">
                <h5 class="items-center font-semibold inline-flex text-lg text-brand-700"><span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-snowflake"></i></span>Ice Dispatched</h5>
                <i class="fas fa-ellipsis-h text-brand-300"></i>
            </div></div>
            <div class="w-full p-6"><h4 class="font-bold text-brand-500 text-2xl mb-4"><?= number_format($totalIce); ?></h4>
                <div class="flex items-center"><span class="items-center font-semibold inline-flex justify-center px-2 py-1 rounded-md bg-green-50 text-green-600 mr-2 text-sm"><i class="fas mr-2 fa-arrow-up"></i>Total</span><p class="font-semibold text-sm text-brand-300">Units sold</p></div>
            </div>
        </div>
    </div>

    <!-- Egg Variant Summary -->
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 space-y-3">
        <h2 class="text-base font-bold text-brand-700">Egg Sizes Breakdown</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
            <div class="bg-brand-50 p-4 rounded-md border border-brand-200">
                <span class="block text-xs font-semibold text-brand-300 uppercase tracking-wider">Small Eggs</span>
                <span class="text-2xl font-bold text-brand-700 mt-1 block"><?= number_format($smallEggs); ?> pcs</span>
            </div>
            <div class="bg-brand-50 p-4 rounded-md border border-brand-200">
                <span class="block text-xs font-semibold text-brand-300 uppercase tracking-wider">Medium Eggs</span>
                <span class="text-2xl font-bold text-brand-700 mt-1 block"><?= number_format($mediumEggs); ?> pcs</span>
            </div>
            <div class="bg-brand-50 p-4 rounded-md border border-brand-200">
                <span class="block text-xs font-semibold text-brand-300 uppercase tracking-wider">Large Eggs</span>
                <span class="text-2xl font-bold text-brand-700 mt-1 block"><?= number_format($largeEggs); ?> pcs</span>
            </div>
        </div>
    </div>

    <!-- Variant Sales Detailed Table -->
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card overflow-hidden">
        <div class="p-6 border-b border-brand-200">
            <h2 class="text-base font-bold text-brand-700">All Product Variant Sales</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[500px]">
                <thead>
                    <tr class="border-b border-brand-200">
                        <th class="px-6 py-3 text-sm font-semibold text-brand-300">Product Name</th>
                        <th class="px-6 py-3 text-sm font-semibold text-brand-300">Variant</th>
                        <th class="px-6 py-3 text-sm font-semibold text-brand-300">Total Quantity Sold</th>
                        <th class="px-6 py-3 text-sm font-semibold text-brand-300 text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($variantBreakdown)): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-brand-300 italic">No variant sales data for selected period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($variantBreakdown as $vb): ?>
                            <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                <td class="px-6 py-3.5 font-semibold text-brand-700"><?= e($vb['product_name']); ?></td>
                                <td class="px-6 py-3.5 text-brand-700"><?= e($vb['variant_name']); ?></td>
                                <td class="px-6 py-3.5 font-semibold text-brand-700"><?= number_format($vb['total_qty']); ?></td>
                                <td class="px-6 py-3.5 text-right font-bold text-brand-500">₱<?= number_format($vb['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
