<?php
/**
 * Authentication and Authorization Guard Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get logged in user details from session
 * 
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['user_name'] ?? '',
        'username' => $_SESSION['user_username'] ?? '',
        'role'     => $_SESSION['user_role'] ?? '',
    ];
}

/**
 * Require user to be logged in, otherwise redirect to login page
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require user to have Admin role
 */
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        $_SESSION['flash_error'] = 'Access denied. Admin privileges required.';
        header('Location: /staff/dashboard.php');
        exit;
    }
}

/**
 * Require user to have Staff or Admin role
 */
function requireStaff(): void {
    requireLogin();
    $role = $_SESSION['user_role'] ?? '';
    if ($role !== 'staff' && $role !== 'admin') {
        http_response_code(403);
        $_SESSION['flash_error'] = 'Access denied. Staff privileges required.';
        header('Location: /login.php');
        exit;
    }
}

/**
 * Helper to safely sanitize HTML output
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Helper to get valid product image URL or fallback SVG Data URI
 */
function getProductImage(?string $imagePath, string $productName = 'Product'): string {
    if (!empty($imagePath)) {
        $cleanPath = ltrim($imagePath, '/');
        $fullPath = __DIR__ . '/../' . $cleanPath;
        if (file_exists($fullPath)) {
            return '/' . $cleanPath;
        }
    }
    $initial = strtoupper(substr($productName, 0, 1) ?: 'P');
    $icon = (strcasecmp($productName, 'egg') === 0) ? '🥚' : ((strcasecmp($productName, 'ice') === 0) ? '🧊' : '📦');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">'
         . '<rect width="300" height="300" fill="#f8fafc"/>'
         . '<rect x="10" y="10" width="280" height="280" rx="16" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="2"/>'
         . '<text x="150" y="130" font-size="72" text-anchor="middle" dominant-baseline="central">' . $icon . '</text>'
         . '<text x="150" y="210" font-family="sans-serif" font-size="20" font-weight="bold" fill="#475569" text-anchor="middle">' . htmlspecialchars($productName) . '</text>'
         . '<text x="150" y="240" font-family="sans-serif" font-size="12" fill="#94a3b8" text-anchor="middle">No Custom Image</text>'
         . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
/**
 * Securely validate and handle product image file uploads
 * 
 * @param array|null $fileInput
 * @return string|null Image web path or null if no file uploaded
 * @throws Exception
 */
function handleProductImageUpload(?array $fileInput): ?string {
    if (!$fileInput || !isset($fileInput['error']) || $fileInput['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($fileInput['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload failed with error code ' . $fileInput['error']);
    }

    // Size limit: 5MB
    if ($fileInput['size'] > 5 * 1024 * 1024) {
        throw new Exception('Image file size must be less than 5MB.');
    }

    // Verify MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileInput['tmp_name']);
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!array_key_exists($mimeType, $allowedMimeTypes)) {
        throw new Exception('Invalid image format. Only JPG, PNG, and WebP images are allowed.');
    }

    // Verify Extension
    $ext = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new Exception('Invalid file extension.');
    }

    $extension = $allowedMimeTypes[$mimeType];
    $fileName = 'prod_' . bin2hex(random_bytes(10)) . '.' . $extension;
    $uploadDir = __DIR__ . '/../uploads/products/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $targetFilePath = $uploadDir . $fileName;

    if (!move_uploaded_file($fileInput['tmp_name'], $targetFilePath)) {
        throw new Exception('Failed to save uploaded image.');
    }

    return 'uploads/products/' . $fileName;
}
