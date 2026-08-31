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

    <!-- Summary Metrics (2 DataAI stat cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-3xl mx-auto">
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
    </div>

    <!-- Sales Trends Chart Card (DataAI-inspired design) -->
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card">
        <div class="w-full p-4 sm:p-6 pb-0">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-brand-200 pb-4 gap-3">
                <div class="flex items-center gap-3">
                    <h5 class="items-center font-semibold inline-flex text-lg text-brand-700">
                        <span class="flex items-center text-brand-500 bg-brand-100 h-9 justify-center mr-2 rounded-sm w-9"><i class="fas fa-chart-area"></i></span>
                        Sales Trends
                    </h5>
                    <span id="salesTrendTotal" class="hidden sm:inline-flex items-center font-semibold px-2.5 py-1 rounded-md bg-green-50 text-green-600 text-sm">
                        ₱0.00
                    </span>
                </div>
                <div class="relative inline-block">
                    <select id="salesTrendYearSelect" class="bg-brand-50 border border-brand-200 text-brand-700 font-semibold text-sm rounded-md px-3.5 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 shadow-sm cursor-pointer transition-all appearance-none">
                        <?php
                        $years = [2026, 2027, 2028, 2029, 2030];
                        $defaultYear = 2026;
                        foreach ($years as $y):
                        ?>
                            <option value="<?= $y; ?>" <?= ($y === $defaultYear) ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-brand-500">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full p-4 sm:p-6 pt-2 sm:pt-4">
            <!-- Mobile total badge -->
            <div class="sm:hidden mb-3">
                <span id="salesTrendTotalMobile" class="inline-flex items-center font-semibold px-2.5 py-1 rounded-md bg-green-50 text-green-600 text-sm">
                    ₱0.00
                </span>
                <span id="salesTrendTxnsMobile" class="inline-flex items-center font-semibold px-2.5 py-1 rounded-md bg-brand-100 text-brand-500 text-sm ml-1">
                    0 transactions
                </span>
            </div>
            <div id="salesTrendChart" style="width:100%; min-height:260px;"></div>
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
<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const brandPrimary   = '#6e4598';
    const brandLight     = '#A78BC5';
    const greenAccent    = '#22c55e';

    let salesTrendChart = null;

    const chartOptions = {
        series: [
            { name: 'Sales (₱)', data: [] },
            { name: 'Transactions', data: [] }
        ],
        chart: {
            type: 'area',
            height: 320,
            fontFamily: 'Open Sans, system-ui, sans-serif',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, easing: 'easeinout', speed: 600 }
        },
        colors: [brandPrimary, greenAccent],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                type: 'vertical',
                opacityFrom: 0.35,
                opacityTo: 0.02,
                stops: [0, 100]
            }
        },
        stroke: { curve: 'smooth', width: [3, 2] },
        dataLabels: { enabled: false },
        xaxis: {
            categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            labels: { style: { colors: '#C9B8DC', fontSize: '12px', fontWeight: 600 } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: [
            {
                min: 1000,
                max: 10000,
                tickAmount: 9,
                title: { text: 'Sales (₱)', style: { color: brandPrimary, fontSize: '12px', fontWeight: 600 } },
                labels: {
                    formatter: function(v) { return '₱' + Math.round(v).toLocaleString('en-PH'); },
                    style: { colors: '#C9B8DC', fontSize: '11px', fontWeight: 600 }
                }
            },
            {
                min: 10,
                max: 100,
                tickAmount: 9,
                opposite: true,
                title: { text: 'Transactions', style: { color: greenAccent, fontSize: '12px', fontWeight: 600 } },
                labels: {
                    formatter: function(v) { return Math.round(v); },
                    style: { colors: '#C9B8DC', fontSize: '11px', fontWeight: 600 }
                }
            }
        ],
        grid: { borderColor: '#F1EDF6', strokeDashArray: 4 },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '13px',
            fontWeight: 600,
            labels: { colors: '#37234B' }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val, opts) {
                    if (opts.seriesIndex === 0) return '₱' + val.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
                    return val + ' txns';
                }
            }
        },
        responsive: [
            {
                breakpoint: 640,
                options: {
                    chart: { height: 240 },
                    xaxis: { labels: { style: { fontSize: '10px' }, rotate: -45, rotateAlways: true } },
                    yaxis: [
                        { min: 1000, max: 10000, tickAmount: 9, title: { text: '' }, labels: { formatter: function(v) { return '₱' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v); }, style: { fontSize: '10px' } } },
                        { min: 10, max: 100, tickAmount: 9, opposite: true, title: { text: '' }, labels: { formatter: function(v) { return Math.round(v); }, style: { fontSize: '10px' } } }
                    ]
                }
            }
        ]
    };

    salesTrendChart = new ApexCharts(document.querySelector('#salesTrendChart'), chartOptions);
    salesTrendChart.render();

    function loadSalesTrend(year) {
        fetch('/actions/sales-trend.php?year=' + year)
            .then(r => r.json())
            .then(data => {
                salesTrendChart.updateSeries([
                    { name: 'Sales (₱)', data: data.sales },
                    { name: 'Transactions', data: data.transactions }
                ]);
                const totalFormatted = '₱' + data.total_sales.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
                const txnsFormatted = data.total_txns + ' transaction' + (data.total_txns !== 1 ? 's' : '');
                document.getElementById('salesTrendTotal').textContent = totalFormatted;
                document.getElementById('salesTrendTotalMobile').textContent = totalFormatted;
                document.getElementById('salesTrendTxnsMobile').textContent = txnsFormatted;
            })
            .catch(err => console.error('Sales trend error:', err));
    }

    const yearSelect = document.getElementById('salesTrendYearSelect');
    if (yearSelect) {
        yearSelect.addEventListener('change', function () {
            loadSalesTrend(this.value);
        });
        loadSalesTrend(yearSelect.value);
    } else {
        loadSalesTrend(2026);
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
