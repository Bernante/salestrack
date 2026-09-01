<?php
/**
 * API Endpoint: Calculate Subtotal
 * 
 * POST /api/calculate-subtotal.php
 * 
 * Provides server-side calculation for client-side preview.
 * Client uses this to show accurate subtotals before submission.
 * Server ALWAYS recalculates before saving (never trusts client).
 * 
 * Request (JSON):
 * {
 *     "quantity": 30,
 *     "unit_price": 168.00,
 *     "selling_unit": "bundle",
 *     "pieces_per_unit": 30
 * }
 * 
 * Response (JSON):
 * {
 *     "success": true,
 *     "subtotal": 168.00,
 *     "formula": "(quantity ÷ pieces_per_unit) × unit_price"
 * }
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lib/RoleGuard.php';
require_once __DIR__ . '/../lib/SaleLineItemCalculator.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Require staff or admin role
try {
    if (!RoleGuard::isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Not authenticated'
        ]);
        exit;
    }

    $user = RoleGuard::current();
    if ($user['role'] !== 'staff' && $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied'
        ]);
        exit;
    }

    // Get JSON body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON'
        ]);
        exit;
    }

    // Extract parameters
    $quantity = $input['quantity'] ?? null;
    $unitPrice = $input['unit_price'] ?? null;
    $sellingUnit = $input['selling_unit'] ?? null;
    $piecesPerUnit = $input['pieces_per_unit'] ?? null;

    // Validate required fields
    if ($quantity === null || $unitPrice === null || $sellingUnit === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: quantity, unit_price, selling_unit'
        ]);
        exit;
    }

    // Calculate subtotal
    $subtotal = SaleLineItemCalculator::calculateSubtotal(
        floatval($quantity),
        floatval($unitPrice),
        strval($sellingUnit),
        $piecesPerUnit !== null ? intval($piecesPerUnit) : null
    );

    // Get formula for display
    $formula = SaleLineItemCalculator::getFormula(strval($sellingUnit));

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'subtotal' => $subtotal,
        'formula' => $formula
    ]);
    exit;

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;

} catch (Exception $e) {
    error_log('Calculate subtotal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error'
    ]);
    exit;
}
