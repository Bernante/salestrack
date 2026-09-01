<?php
/**
 * RoleGuard: Centralized Role-Based Access Control
 * 
 * Encapsulates:
 * - Session state management
 * - User context retrieval
 * - Role-based authorization checks
 * - Redirect logic for unauthorized access
 * 
 * Usage:
 *   RoleGuard::requireLogin();    // Ensure user is authenticated
 *   RoleGuard::requireAdmin();    // Ensure admin role
 *   RoleGuard::requireStaff();    // Ensure staff or admin role
 *   $user = RoleGuard::current(); // Get current user
 */

class RoleGuard
{
    /**
     * Ensure session is initialized
     */
    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Check if a user is logged in
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        self::ensureSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get logged-in user details from session
     * 
     * @return array|null User data or null if not logged in
     */
    public static function current(): ?array
    {
        if (!self::isLoggedIn()) {
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
     * 
     * @return void
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            $_SESSION['flash_error'] = 'Please log in to access this page.';
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Require user to have Admin role
     * 
     * @return void
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if ((self::current()['role'] ?? '') !== 'admin') {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Access denied. Admin privileges required.';
            header('Location: /staff/dashboard.php');
            exit;
        }
    }

    /**
     * Require user to have Staff or Admin role
     * 
     * @return void
     */
    public static function requireStaff(): void
    {
        self::requireLogin();
        $role = self::current()['role'] ?? '';
        if ($role !== 'staff' && $role !== 'admin') {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Access denied. Staff privileges required.';
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Check if current user has a specific role
     * 
     * @param string $role
     * @return bool
     */
    public static function hasRole(string $role): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }
        return (self::current()['role'] ?? '') === $role;
    }

    /**
     * Set user session data (used during login)
     * 
     * @param int $userId
     * @param string $name
     * @param string $username
     * @param string $role
     * @return void
     */
    public static function setSession(int $userId, string $name, string $username, string $role): void
    {
        self::ensureSession();
        $_SESSION['user_id']       = $userId;
        $_SESSION['user_name']     = $name;
        $_SESSION['user_username'] = $username;
        $_SESSION['user_role']     = $role;
    }

    /**
     * Clear user session (used during logout)
     * 
     * @return void
     */
    public static function clearSession(): void
    {
        self::ensureSession();
        session_destroy();
    }
}
