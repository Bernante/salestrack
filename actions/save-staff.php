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

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
$status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

if (empty($name) || empty($username) || empty($password)) {
    $_SESSION['flash_error'] = 'All fields are required.';
    header('Location: /admin/staff-create.php');
    exit;
}

$db = getDBConnection();

try {
    // Check if username already exists
    $stmtCheck = $db->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmtCheck->execute([':username' => $username]);
    if ($stmtCheck->fetch()) {
        $_SESSION['flash_error'] = 'Username is already taken. Please choose another.';
        header('Location: /admin/staff-create.php');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare('
        INSERT INTO users (name, username, password, role, status)
        VALUES (:name, :username, :password, :role, :status)
    ');
    $stmt->execute([
        ':name'     => $name,
        ':username' => $username,
        ':password' => $hashedPassword,
        ':role'     => $role,
        ':status'   => $status
    ]);

    $_SESSION['flash_success'] = 'User created successfully!';
    header('Location: /admin/staff.php');
    exit;

} catch (Exception $e) {
    error_log('Save Staff Error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Failed to create user. Please try again.';
    header('Location: /admin/staff-create.php');
    exit;
}
