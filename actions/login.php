<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$csrfToken = $_POST['csrf_token'] ?? '';

// Validate CSRF
if (!validateCsrfToken($csrfToken)) {
    $_SESSION['flash_error'] = 'Invalid request token. Please try again.';
    header('Location: /login.php');
    exit;
}

if (empty($username) || empty($password)) {
    $_SESSION['flash_error'] = 'Please enter both username and password.';
    header('Location: /login.php');
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare('SELECT id, name, username, password, role, status FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['flash_error'] = 'Invalid username or password.';
        header('Location: /login.php');
        exit;
    }

    if ($user['status'] !== 'active') {
        $_SESSION['flash_error'] = 'Your account is inactive. Please contact system admin.';
        header('Location: /login.php');
        exit;
    }

    // Authentication Successful
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];

    // Redirect by role
    if ($user['role'] === 'admin') {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /staff/dashboard.php');
    }
    exit;

} catch (Exception $e) {
    error_log('Login Exception: ' . $e->getMessage());
    if (strpos($e->getMessage(), 'Database connection failed') !== false) {
        $_SESSION['flash_error'] = $e->getMessage();
    } else {
        $_SESSION['flash_error'] = 'An unexpected error occurred. Please try again.';
    }
    header('Location: /login.php');
    exit;
}
