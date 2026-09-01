<?php
/**
 * Authentication Bootstrap Module
 * 
 * Provides global functions for backward compatibility.
 * New code should use the RoleGuard, ProductImageService, and HtmlSanitizer classes directly.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/RoleGuard.php';
require_once __DIR__ . '/../lib/ProductImageService.php';
require_once __DIR__ . '/../lib/HtmlSanitizer.php';

// ============================================================================
// BACKWARD COMPATIBILITY FUNCTIONS
// These functions are kept for existing code compatibility.
// New code should use the classes directly.
// ============================================================================

/**
 * @deprecated Use RoleGuard::isLoggedIn() instead
 */
function isLoggedIn(): bool
{
    return RoleGuard::isLoggedIn();
}

/**
 * @deprecated Use RoleGuard::current() instead
 */
function getCurrentUser(): ?array
{
    return RoleGuard::current();
}

/**
 * @deprecated Use RoleGuard::requireLogin() instead
 */
function requireLogin(): void
{
    RoleGuard::requireLogin();
}

/**
 * @deprecated Use RoleGuard::requireAdmin() instead
 */
function requireAdmin(): void
{
    RoleGuard::requireAdmin();
}

/**
 * @deprecated Use RoleGuard::requireStaff() instead
 */
function requireStaff(): void
{
    RoleGuard::requireStaff();
}

/**
 * @deprecated Use HtmlSanitizer::escape() or HtmlSanitizer::e() instead
 */
function e(?string $string): string
{
    return HtmlSanitizer::escape($string);
}

/**
 * @deprecated Use ProductImageService->getImageUrl() instead
 */
function getProductImage(?string $imagePath, string $productName = 'Product'): string
{
    $imageService = new ProductImageService();
    return $imageService->getImageUrl($imagePath, $productName);
}

/**
 * @deprecated Use ProductImageService->handleUpload() instead
 */
function handleProductImageUpload(?array $fileInput): ?string
{
    $imageService = new ProductImageService();
    return $imageService->handleUpload($fileInput);
}
