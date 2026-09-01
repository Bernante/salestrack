<?php
/**
 * Admin Authentication Guard
 * 
 * @deprecated Include includes/auth.php directly and use RoleGuard::requireAdmin()
 * This file kept for backward compatibility only.
 */

require_once __DIR__ . '/auth.php';
RoleGuard::requireAdmin();

