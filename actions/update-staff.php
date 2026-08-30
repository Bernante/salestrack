<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/staff.php');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid request token.';
    header('Location: /admin/staff.php');
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
$status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

if ($userId <= 0 || empty($name) || empty($username)) {
    $_SESSION['flash_error'] = 'Name and username are required.';
    header('Location: /admin/staff.php');
    exit;
}

$db = getDBConnection();

try {
    // Check if username is used by another user
    $stmtCheck = $db->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
    $stmtCheck->execute([':username' => $username, ':id' => $userId]);
    if ($stmtCheck->fetch()) {
        $_SESSION['flash_error'] = 'Username is already taken by another account.';
        header('Location: /admin/staff-edit.php?id=' . $userId);
        exit;
    }

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET name = :name, username = :username, password = :password, role = :role, status = :status WHERE id = :id');
        $stmt->execute([
            ':name'     => $name,
            ':username' => $username,
            ':password' => $hashedPassword,
            ':role'     => $role,
            ':status'   => $status,
            ':id'       => $userId
        ]);
    } else {
        $stmt = $db->prepare('UPDATE users SET name = :name, username = :username, role = :role, status = :status WHERE id = :id');
        $stmt->execute([
            ':name'     => $name,
            ':username' => $username,
            ':role'     => $role,
            ':status'   => $status,
            ':id'       => $userId
        ]);
    }

    $_SESSION['flash_success'] = 'User account updated successfully!';
    header('Location: /admin/staff.php');
    exit;

} catch (Exception $e) {
    error_log('Update Staff Error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Failed to update user account.';
    header('Location: /admin/staff-edit.php?id=' . $userId);
    exit;
}
