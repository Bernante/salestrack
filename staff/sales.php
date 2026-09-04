<?php
$pageTitle = 'My Sales History';
require_once __DIR__ . '/../includes/staff-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');

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

$where = ['s.user_id = :user_id'];
$params = [':user_id' => $userId];

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
        // Search by transaction number (existing behavior)
        $where[] = 's.transaction_number LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT s.id, s.transaction_number, s.total_amount, s.amount_paid, s.change_amount, s.created_at, s.payment_status, s.status
    FROM sales s
    $whereSql
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-brand-700">My Sales History</h1>
            <p class="text-sm text-brand-300">View transactions you have recorded at the store.</p>
        </div>
        
        <!-- Filter Form -->
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-2 w-full">
            <input type="text" name="search" value="<?= e($search); ?>" placeholder="Transaction # or Date" class="px-4 py-2.5 rounded-md border border-brand-200 text-sm focus:outline-none focus:border-brand-500 w-full sm:w-48">
            <button type="submit" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-md text-sm font-semibold shadow-sm transition-colors">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/staff/sales.php" class="text-sm font-semibold text-brand-300 hover:text-brand-500 px-2 py-2 text-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card overflow-hidden">
        <!-- Mobile Card View (hidden on md+) -->
        <div class="md:hidden">
            <div class="p-6 space-y-3">
                <?php if (empty($sales)): ?>
                    <div class="text-center text-sm text-brand-300 italic py-8">No sales history found.</div>
                <?php else: ?>
                    <?php foreach ($sales as $s): ?>
                        <div class="border border-brand-100 rounded-md p-4 space-y-2 hover:bg-brand-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-brand-300 uppercase">Transaction</span>
                                <span class="font-mono font-bold text-brand-700 text-sm"><?= e($s['transaction_number']); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-brand-300 uppercase">Total</span>
                                <span class="font-bold text-brand-500">₱<?= number_format($s['total_amount'], 2); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-brand-300 uppercase">Date/Time</span>
                                <span class="text-sm text-brand-300"><?= date('m/d h:i A', strtotime($s['created_at'])); ?></span>
                            </div>
                            <div class="pt-2 border-t border-brand-100">
                                <div class="flex gap-1.5">
                                    <a href="/staff/sale-details.php?id=<?= $s['id']; ?>" class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">View Receipt</a>
                                    <div class="flex-1 inline-block min-w-[100px]">
                                        <?php if ($s['status'] === 'cancelled'): ?>
                                            <span class="inline-flex items-center justify-center w-full px-3 py-2 bg-red-50 text-red-600 border border-red-200 rounded-sm text-sm font-semibold transition-colors uppercase tracking-wider">Cancelled</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center justify-center w-full px-3 py-2 bg-green-50 text-green-600 border border-green-200 rounded-sm text-sm font-semibold transition-colors uppercase tracking-wider">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Desktop Table View (hidden on mobile) -->
        <div class="hidden md:block">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-brand-200">
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Transaction #</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Total Amount</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Amount Paid</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Change</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300">Date / Time</th>
                            <th class="px-6 py-3.5 text-sm font-semibold text-brand-300 text-right">Actions</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-brand-300 italic">No sales history found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                            <tr class="border-b border-brand-100 hover:bg-brand-50 transition-colors">
                                <td class="px-6 py-4 font-semibold font-mono text-brand-700 text-sm"><?= e($s['transaction_number']); ?></td>
                                <td class="px-6 py-4 font-bold text-brand-500 text-sm">₱<?= number_format($s['total_amount'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-700 text-sm">₱<?= number_format($s['amount_paid'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-300 text-sm">₱<?= number_format($s['change_amount'], 2); ?></td>
                                <td class="px-6 py-4 text-brand-300 text-sm"><?= date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                                <td class="px-6 py-4 text-right space-x-1.5">
                                    <div class="inline-block min-w-[120px]">
                                        <a href="/staff/sale-details.php?id=<?= $s['id']; ?>" class="inline-flex items-center justify-center w-full px-3 py-1.5 bg-brand-100 hover:bg-brand-200 text-brand-500 rounded-sm text-sm font-semibold transition-colors">View Receipt</a>
                                    </div>
                                    <div class="inline-block min-w-[120px]">
                                        <?php if ($s['status'] === 'cancelled'): ?>
                                            <span class="inline-flex items-center justify-center w-full px-3 py-1.5 bg-red-50 text-red-600 border border-red-200 rounded-sm text-sm font-semibold transition-colors uppercase tracking-wider">Cancelled</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center justify-center w-full px-3 py-1.5 bg-green-50 text-green-600 border border-green-200 rounded-sm text-sm font-semibold transition-colors uppercase tracking-wider">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Cancellation Modal Dialog for Staff -->
<div id="staffCancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-brand-900/60 backdrop-blur-sm p-4 hidden">
    <div class="bg-white w-full max-w-md rounded-md p-6 shadow-card border border-brand-200 space-y-5">
        <div class="flex items-start justify-between border-b border-brand-200 pb-3">
            <div>
                <h3 class="text-lg font-bold text-brand-700">Cancel this order?</h3>
                <p class="text-xs text-brand-300 mt-0.5">Transaction: <span id="modalTxnNum" class="font-mono font-semibold text-brand-700"></span></p>
            </div>
            <button type="button" onclick="closeStaffCancelModal()" class="text-brand-300 hover:text-brand-500 text-lg font-bold">&times;</button>
        </div>

        <form id="staffCancelForm" class="space-y-4">
            <?= getCsrfField(); ?>
            <input type="hidden" name="sale_id" id="modalSaleId" value="">
            <p class="text-sm text-brand-700 font-medium">Are you sure you want to cancel this order? This action will mark the transaction as cancelled.</p>
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-200">
                <button type="button" onclick="closeStaffCancelModal()" class="px-5 py-2.5 bg-brand-100 hover:bg-brand-200 text-brand-500 font-semibold text-sm rounded-sm transition-colors">Keep Order</button>
                <button type="submit" id="staffCancelSubmitBtn" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm rounded-sm shadow-sm transition-colors">Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<script>
let cancelModalIsSubmitting = false;

// Explicitly attach to window to ensure global accessibility
window.openStaffCancelModal = function(saleId, txnNum) {
    document.getElementById("modalSaleId").value = saleId;
    document.getElementById("modalTxnNum").textContent = txnNum;
    document.getElementById("staffCancelModal").classList.remove("hidden");
    cancelModalIsSubmitting = false;
}

window.closeStaffCancelModal = function() {
    document.getElementById("staffCancelModal").classList.add("hidden");
    cancelModalIsSubmitting = false;
}
window.showCancelNotification = function(message, isSuccess = true) {
    let notification = document.getElementById("cancelNotification");
    if (!notification) {
        notification = document.createElement("div");
        notification.id = "cancelNotification";
        notification.className = "fixed top-4 right-4 z-40 max-w-sm";
        document.body.appendChild(notification);
    }
    const content = document.createElement("div");
    const icon = document.createElement("i");
    const messageEl = document.createElement("span");
    if (isSuccess) {
        content.className = "rounded-md p-4 shadow-card border border-brand-200 flex items-center gap-3 bg-brand-50";
        icon.className = "fas fa-check-circle text-lg flex-shrink-0 text-brand-500";
        messageEl.className = "text-sm font-semibold text-brand-700";
    } else {
        content.className = "rounded-md p-4 shadow-card border border-red-200 flex items-center gap-3 bg-red-50";
        icon.className = "fas fa-exclamation-circle text-lg flex-shrink-0 text-red-500";
        messageEl.className = "text-sm font-semibold text-red-700";
    }
    messageEl.textContent = message;
    content.appendChild(icon);
    content.appendChild(messageEl);
    notification.innerHTML = "";
    notification.appendChild(content);
    notification.classList.remove("hidden");
    setTimeout(() => { notification.classList.add("hidden"); }, 4000);
}
document.addEventListener("DOMContentLoaded", () => {
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".cancelOrderBtn");
        if (btn) {
            const saleId = btn.getAttribute("data-sale-id");
            const txnNum = btn.getAttribute("data-transaction-num");
            openStaffCancelModal(saleId, txnNum);
        }
    });
    const form = document.getElementById("staffCancelForm");
    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (cancelModalIsSubmitting) return;
            const submitBtn = document.getElementById("staffCancelSubmitBtn");
            const saleId = document.getElementById("modalSaleId").value;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            cancelModalIsSubmitting = true;
            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = "Processing...";
            try {
                const response = await fetch("/actions/cancel-sale.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ "sale_id": saleId, "csrf_token": csrfToken })
                });
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        closeStaffCancelModal();
                        showCancelNotification(data.message || "Order cancelled successfully", true);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showCancelNotification(data.message || "Failed to cancel order", false);
                        cancelModalIsSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                } catch (parseError) {
                    showCancelNotification("Failed to process response from server", false);
                    cancelModalIsSubmitting = false;
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                showCancelNotification("Network error: Unable to cancel order", false);
                cancelModalIsSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
