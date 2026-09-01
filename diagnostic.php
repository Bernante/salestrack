<?php
/**
 * SalesTrack Deployment Diagnostic
 * Place this file in htdocs/ and visit: https://salestracks.infinityfreeapp.com/diagnostic.php
 * This will show you exactly what's wrong with your deployment
 */

echo "<h1>SalesTrack Deployment Diagnostic</h1>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace;'>";

// Check critical files
$criticalFiles = [
    'index.php',
    'login.php',
    'logout.php',
    'config/database.php',
    'includes/auth.php',
    'includes/header.php',
    'includes/footer.php',
    'admin/dashboard.php',
    'staff/dashboard.php'
];

echo "=== CRITICAL FILES CHECK ===\n";
foreach ($criticalFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? '✓ EXISTS' : '✗ MISSING';
    echo "$status: $file\n";
}

echo "\n=== DIRECTORY STRUCTURE ===\n";
$dirs = ['admin', 'staff', 'includes', 'config', 'actions', 'assets', 'uploads', 'database'];
foreach ($dirs as $dir) {
    $exists = is_dir(__DIR__ . '/' . $dir);
    $status = $exists ? '✓ EXISTS' : '✗ MISSING';
    if ($exists) {
        $fileCount = count(glob(__DIR__ . '/' . $dir . '/*'));
        echo "$status: /$dir/ ($fileCount files)\n";
    } else {
        echo "$status: /$dir/\n";
    }
}

echo "\n=== DATABASE CONNECTION ===\n";
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();
    echo "✓ Database connection successful!\n";
    
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables found: " . implode(', ', $tables) . "\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== PERMISSIONS CHECK ===\n";
echo "Current directory: " . __DIR__ . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "File upload enabled: " . (ini_get('file_uploads') ? 'Yes' : 'No') . "\n";
echo "Max upload size: " . ini_get('upload_max_filesize') . "\n";

echo "\n=== NEXT STEPS ===\n";
echo "1. If files are MISSING, re-upload the ZIP to htdocs/ and extract\n";
echo "2. If database connection FAILED, check config/database.php credentials\n";
echo "3. If everything is ✓, delete this file and visit: https://salestracks.infinityfreeapp.com/login.php\n";

echo "</pre>";
?>
