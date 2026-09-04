<?php
$pageTitle = 'All Sales History';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = getDBConnection();
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

/**
 * Detect date and return result:
 * - For full dates (with year): ['type' => 'full_date', 'date' => 'YYYY-MM-DD']
 * - For year-less dates (month/day only): ['type' => 'yearless_date', 'month' => M, 'day' => D]
 * - For non-dates: null
 */
function detectDate($input) {
    $input = trim($input);
    
    // 1. ISO format with year: YYYY-MM-DD
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $input, $m)) {
        $month = intval($m[2]);
        $day = intval($m[3]);
        $year = intval($m[1]);
        $d = mktime(0, 0, 0, $month, $day, $year);
        if ($d === false) return null;
        return ['type' => 'full_date', 'date' => date('Y-m-d', $d)];
    }
    
    // 2. US formats with year: MM/DD/YYYY, MM-DD-YYYY, M/D/YYYY
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $input, $m)) {
        $month = intval($m[1]);
        $day = intval($m[2]);
        $year = intval($m[3]);
        $d = mktime(0, 0, 0, $month, $day, $year);
        if ($d === false) return null;
        return ['type' => 'full_date', 'date' => date('Y-m-d', $d)];
    }
    
    // 3. Shortened year: MM/DD/YY (must have 3 segments to distinguish from yearless)
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})$/', $input, $m)) {
        $month = intval($m[1]);
        $day = intval($m[2]);
        $year = intval($m[3]);
        // Assume 00-50 = 2000-2050, 51-99 = 1951-1999
        $year = $year <= 50 ? 2000 + $year : 1900 + $year;
        $d = mktime(0, 0, 0, $month, $day, $year);
        if ($d === false) return null;
        return ['type' => 'full_date', 'date' => date('Y-m-d', $d)];
    }
    
    // 4. Text format with year: "Sep 03, 2026" or "Sep 3, 2026" (with optional comma)
    if (preg_match('/^([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})$/', $input, $m)) {
        $d = strtotime($m[1] . ' ' . $m[2] . ' ' . $m[3]);
        if ($d === false) return null;
        return ['type' => 'full_date', 'date' => date('Y-m-d', $d)];
    }
    
    // 5. Year-less US numeric: MM/DD or MM-DD (no year, no 3rd segment)
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})$/', $input, $m)) {
        $month = intval($m[1]);
        $day = intval($m[2]);
        // Validate the month/day combination
        $d = mktime(0, 0, 0, $month, $day, 2026); // Use arbitrary year for validation
        if ($d === false) return null;
        return ['type' => 'yearless_date', 'month' => $month, 'day' => $day];
    }
    
    // 6. Year-less text format: "Sep 2", "Sep 02", "September 2", etc. (no year)
    if (preg_match('/^([A-Za-z]+)\s+(\d{1,2})$/', $input, $m)) {
        $d = strtotime($m[1] . ' ' . $m[2] . ' 2026'); // Use arbitrary year for validation
        if ($d === false) return null;
        // Extract actual month and day from validated timestamp
        $validated_month = intval(date('m', $d));
        $validated_day = intval(date('d', $d));
        return ['type' => 'yearless_date', 'month' => $validated_month, 'day' => $validated_day];
    }
    
    return null;
}

$where = [];
$params = [];

if (!empty($search)) {
    $dateResult = detectDate($search);
    
    if ($dateResult) {
        if ($dateResult['type'] === 'full_date') {
            // Full date with year: exact date match
            $where[] = 'DATE(s.created_at) = :search_date';
            $params[':search_date'] = $dateResult['date'];
        } else { // yearless_date
            // Year-less date: match month and day across all years
            $where[] = 'MONTH(s.created_at) = :search_month AND DAY(s.created_at) = :search_day';
            $params[':search_month'] = $dateResult['month'];
            $params[':search_day'] = $dateResult['day'];
        }
    } else {
        // Search by transaction number or staff name
        $where[] = '(s.transaction_number LIKE :search OR u.name LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }
}

if (!empty($statusFilter) && in_array($statusFilter, ['completed', 'cancelled'])) {
    $where[] = 's.status = :status';
    $params[':status'] = $statusFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT s.*, u.name AS staff_name
    FROM sales s
    JOIN users u ON s.user_id = u.id
    $whereSql
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-brand-700">All Sales Transactions History</h1>
            <p class="text-sm text-brand-300">Monitor all completed and cancelled customer receipts in real-time.</p>
        </div>
        
        <!-- Filter Form -->
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-2.5 w-full">
            <input type="text" name="search" value="<?= e($search); ?>" placeholder="Search Txn #, Staff, or Date" class="px-4 py-2 rounded-md border border-brand-200 text-sm focus:outline-none focus:border-brand-500 w-full sm:w-56 h-10">
            <select name="status" class="px-3.5 py-2 rounded-md border border-brand-200 text-sm font-semibold text-brand-700 focus:outline-none focus:border-brand-500 h-10">
                <option value="">All Statuses</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-md text-sm font-semibold shadow-sm transition-colors h-10">Filter</button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="/admin/sales.php" class="text-sm font-semibold text-brand-300 hover:text-brand-500 px-2 py-2">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card overflow-hidden">
        <!-- Mobile Card View (hidden on md+) -->
        <div class="md:hidden p-6 space-y-3">
            <?php if (empty($sales)): ?>
                <div class="text-center text-sm text-brand-300 italic py-8">No transaction records found.</div>
            <?php else: ?>
                <?php foreach ($sales as $s): ?>
                    <div class="border border-brand-100 rounded-md p-4 space-y-2 hover:bg-brand-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-brand-300 uppercase">Txn #</span>
                            <span class="font-mono font-bold text-brand-700 text-sm"><?= e($s['transaction_number']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-brand-300 uppercase">Staff</span>
                            <span class="text-sm text-brand-700"><?= e($s['staff_name']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-brand-300 uppercase">Total Amount</span>
                            <span class="font-bold text-brand-500">₱<?= number_format($s['total_amount'], 2); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-brand-300 uppercase">Status</span>
                            <span class="text-sm text-brand-700"><?= e($s['status']); ?></span>
                        </div>
                        <div class="pt-2 border-t border-brand-100">
                            <a href="/admin/sale-details.php?id=<?= $s['id']; ?>" class="block px-3 py-2 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors text-center">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Desktop Table View (hidden on mobile) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-brand-200">
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Transaction #</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Staff</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Total Amount</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Amount Paid</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Change</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Date / Time</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Status</th>
                        <th class="px-6 py-3.5 text-sm font-semibold text-brand-300 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="8" class="px-6 py-8 text-center text-sm text-brand-300 italic">No transaction records found matching search filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                            <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                <td class="px-6 py-4 font-semibold font-mono text-brand-700 text-sm"><?= e($s['transaction_number']); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm"><?= e($s['staff_name']); ?></td>
                                <td class="px-6 py-4 font-bold text-brand-500 text-sm">₱<?= number_format($s['total_amount'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm">₱<?= number_format($s['amount_paid'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-300 text-sm">₱<?= number_format($s['change_amount'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-300 text-sm"><?= date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider <?= $s['status'] === 'completed' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'; ?>">
                                        <?= e(ucfirst($s['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/admin/sale-details.php?id=<?= $s['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">Receipt Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
