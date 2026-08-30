<?php
/**
 * API: Sales Trend Data
 * Returns monthly sales totals for a given year.
 * GET /actions/sales-trend.php?year=2024
 */
require_once __DIR__ . '/../includes/admin-auth.php';

header('Content-Type: application/json');

$db = getDBConnection();
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get monthly sales totals for the requested year
$stmt = $db->prepare('
    SELECT 
        MONTH(created_at) AS month_num,
        COALESCE(SUM(total_amount), 0) AS total_sales,
        COUNT(id) AS transaction_count
    FROM sales
    WHERE status = "completed" AND YEAR(created_at) = :year
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at) ASC
');
$stmt->execute([':year' => $year]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build full 12-month array (fill missing months with 0)
$monthly = [];
for ($m = 1; $m <= 12; $m++) {
    $monthly[$m] = ['sales' => 0, 'transactions' => 0];
}
foreach ($rows as $r) {
    $monthly[intval($r['month_num'])] = [
        'sales'        => round(floatval($r['total_sales']), 2),
        'transactions' => intval($r['transaction_count']),
    ];
}

// Flatten into arrays for chart
$salesData = [];
$txnData   = [];
for ($m = 1; $m <= 12; $m++) {
    $salesData[] = $monthly[$m]['sales'];
    $txnData[]   = $monthly[$m]['transactions'];
}

// Total for the year
$yearTotal = array_sum($salesData);
$yearTxns  = array_sum($txnData);

echo json_encode([
    'year'         => $year,
    'labels'       => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    'sales'        => $salesData,
    'transactions' => $txnData,
    'total_sales'  => $yearTotal,
    'total_txns'   => $yearTxns,
]);
