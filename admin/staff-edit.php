<?php
$pageTitle = 'Edit User / Staff Account';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: /admin/staff.php');
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare('SELECT id, name, username, role, status FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['flash_error'] = 'User account not found.';
    header('Location: /admin/staff.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto py-6 space-y-6">
    <div class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-brand-700">Edit User: <?= e($user['name']); ?></h1>
        <a href="/admin/staff.php" class="text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors">&larr; Back to Staff</a>
    </div>

    <form action="/actions/update-staff.php" method="POST" class="w-full rounded-md border border-brand-200 bg-white shadow-card p-6 space-y-5">
        <?= getCsrfField(); ?>
        <input type="hidden" name="user_id" value="<?= $user['id']; ?>">

        <div>
            <label for="name" class="block text-sm font-semibold text-brand-700 mb-1">Full Name *</label>
            <input type="text" id="name" name="name" value="<?= e($user['name']); ?>" required class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
        </div>

        <div>
            <label for="username" class="block text-sm font-semibold text-brand-700 mb-1">Username *</label>
            <input type="text" id="username" name="username" value="<?= e($user['username']); ?>" required class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-brand-700 mb-1">New Password (leave blank to keep current password)</label>
            <input type="password" id="password" name="password" placeholder="Enter new password if changing" class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm text-brand-700 focus:outline-none focus:border-brand-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="role" class="block text-sm font-semibold text-brand-700 mb-1">System Role *</label>
                <select id="role" name="role" class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm font-semibold text-brand-700 focus:outline-none focus:border-brand-500">
                    <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff (Sales Recording)</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin (Full Access & Remote Monitoring)</option>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-semibold text-brand-700 mb-1">Account Status *</label>
                <select id="status" name="status" class="w-full px-4 py-2.5 rounded-md border border-brand-200 text-sm font-semibold text-brand-700 focus:outline-none focus:border-brand-500">
                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-brand-200 flex justify-end gap-3">
            <a href="/admin/staff.php" class="px-5 py-2.5 rounded-md border border-brand-200 text-brand-700 font-semibold text-sm hover:bg-brand-50 transition-colors">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-md bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm shadow-sm transition-colors">Update User Account</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
