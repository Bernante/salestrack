require_once __DIR__ . '/includes/auth.php';
<?php
$db = getDBConnection();
$db->query("UPDATE sales SET status = 'completed', cancellation_reason = NULL WHERE id = 2");
echo "DONE";
