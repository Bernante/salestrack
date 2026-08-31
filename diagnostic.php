<?php
/**
 * SalesTrack Deployment Diagnostic Report
 * Generated: 2026-08-31
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== SalesTrack Deployment Diagnostic Report ===\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";

echo "1. PHP Environment\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "   OS: " . php_uname() . "\n\n";

echo "2. Directory Information\n";
echo "   Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "   Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "   Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "   Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "   Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n\n";

echo "3. File System\n";
echo "   Current Directory: " . getcwd() . "\n";
echo "   Is Writable: " . (is_writable('.') ? 'YES' : 'NO') . "\n\n";

echo "4. Module Status\n";
echo "   mod_rewrite: " . (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()) ? 'ENABLED' : 'UNKNOWN/DISABLED') . "\n";
echo "   All Modules: " . (function_exists('apache_get_modules') ? implode(', ', apache_get_modules()) : 'Not available') . "\n\n";

echo "5. File Check\n";
$files_to_check = [
    'index.php',
    'login.php',
    '.htaccess',
    'config/database.php',
    'includes/auth.php'
];

foreach ($files_to_check as $file) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file, '/');
    $exists = file_exists($path) ? 'EXISTS' : 'MISSING';
    $readable = is_readable($path) ? 'READABLE' : 'NOT READABLE';
    echo "   $file: $exists [$readable]\n";
}

echo "\n6. Database Connection Test\n";
try {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    echo "   Status: CONNECTED\n";
    echo "   Database: " . DB_NAME . "\n";
    echo "   Host: " . DB_HOST . "\n";
} catch (Exception $e) {
    echo "   Status: FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== End of Report ===\n";
?>
