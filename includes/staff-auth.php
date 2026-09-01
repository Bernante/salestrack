<?php
/**
 * Staff Authentication Guard
 * 
 * @deprecated Include includes/auth.php directly and use RoleGuard::requireStaff()
 * This file kept for backward compatibility only.
 */

require_once __DIR__ . '/auth.php';
RoleGuard::requireStaff();

