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

    <!-- Sales Trends Chart Card (DataAI-inspired design) -->
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card mb-8">
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
                <div class="flex items-center gap-2 flex-wrap">
                    <?php
                    $currentYear = intval(date('Y'));
                    for ($y = $currentYear - 2; $y <= $currentYear; $y++):
                    ?>
                    <button type="button"
                        class="sales-trend-year-btn px-3 py-1.5 rounded-md text-sm font-semibold transition-all duration-200 <?= ($y === $currentYear) ? 'bg-brand-500 text-white shadow-md' : 'bg-brand-100 text-brand-500 hover:bg-brand-200'; ?>"
                        data-year="<?= $y; ?>">
                        <?= $y; ?>
                    </button>
                    <?php endfor; ?>
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
                        { opposite: true, title: { text: '' }, labels: { formatter: function(v) { return Math.round(v); }, style: { fontSize: '10px' } } }
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

    document.querySelectorAll('.sales-trend-year-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.sales-trend-year-btn').forEach(b => {
                b.className = 'sales-trend-year-btn px-3 py-1.5 rounded-md text-sm font-semibold transition-all duration-200 bg-brand-100 text-brand-500 hover:bg-brand-200';
            });
            this.className = 'sales-trend-year-btn px-3 py-1.5 rounded-md text-sm font-semibold transition-all duration-200 bg-brand-500 text-white shadow-md';
            loadSalesTrend(this.dataset.year);
        });
    });

    loadSalesTrend(<?= $currentYear; ?>);
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
