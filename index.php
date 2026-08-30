<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /staff/dashboard.php');
    }
    exit;
}

header('Location: /login.php');
exit;
