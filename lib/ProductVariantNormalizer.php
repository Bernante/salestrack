<?php
/**
 * ProductVariantNormalizer: Standardize variant data formats
 * 
 * Encapsulates data transformation across all layers:
 * - Database format → Client format (for JSON)
 * - Client format → Storage format (for processing)
 * - Defines canonical variant data structure
 */

class ProductVariantNormalizer
{
    /**
     * Normalize database row to client format (for JSON)
     */
    public static function normalizeForClient(array $dbRow): array
    {
        return [
            'id' => intval($dbRow['variant_id'] ?? $dbRow['id'] ?? 0),
            'product_id' => intval($dbRow['product_id'] ?? 0),
            'product_name' => strval($dbRow['product_name'] ?? ''),
            'variant_name' => strval($dbRow['variant_name'] ?? ''),
            'selling_unit' => strval($dbRow['selling_unit'] ?? 'piece'),
            'pieces_per_unit' => intval($dbRow['pieces_per_unit'] ?? 1),
            'price' => floatval($dbRow['price'] ?? 0),
            'status' => strval($dbRow['variant_status'] ?? 'active'),
        ];
    }

    /**
     * Normalize client format to storage format
     */
    public static function normalizeForStorage(array $clientData): array
    {
        return [
            'id' => intval($clientData['id'] ?? 0),
            'product_id' => intval($clientData['product_id'] ?? 0),
            'product_name' => strval($clientData['product_name'] ?? ''),
            'variant_name' => strval($clientData['variant_name'] ?? ''),
            'selling_unit' => strval($clientData['selling_unit'] ?? 'piece'),
            'pieces_per_unit' => intval($clientData['pieces_per_unit'] ?? 1),
            'price' => floatval($clientData['price'] ?? 0),
            'status' => strval($clientData['status'] ?? 'active'),
        ];
    }

    /**
     * Normalize cart item from client submission
     */
    public static function normalizeCartItem(array $cartItem): array
    {
        return [
            'product_variant_id' => intval($cartItem['product_variant_id'] ?? 0),
            'quantity_units' => intval($cartItem['quantity_units'] ?? 0),
            'selling_unit' => strval($cartItem['selling_unit'] ?? 'piece'),
            'pieces_per_unit' => intval($cartItem['pieces_per_unit'] ?? 1),
        ];
    }

    /**
     * Validate variant data
     */
    public static function validate(array $variant): array
    {
        $errors = [];

        $required = ['id', 'product_id', 'product_name', 'variant_name', 
                     'selling_unit', 'pieces_per_unit', 'price'];

        foreach ($required as $field) {
            if (!isset($variant[$field])) {
                $errors[] = "Missing required field: $field";
            }
        }

        if (isset($variant['id']) && !is_numeric($variant['id'])) {
            $errors[] = "ID must be numeric";
        }

        if (isset($variant['price']) && !is_numeric($variant['price'])) {
            $errors[] = "Price must be numeric";
        }

        if (isset($variant['pieces_per_unit']) && intval($variant['pieces_per_unit']) < 1) {
            $errors[] = "Pieces per unit must be >= 1";
        }

        $validUnits = ['piece', 'bundle', 'tray', 'half_tray'];
        if (isset($variant['selling_unit']) && 
            !in_array($variant['selling_unit'], $validUnits, true)) {
            $errors[] = "Invalid selling unit";
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Get canonical variant schema
     */
    public static function getSchema(): array
    {
        return [
            'id' => 'int',
            'product_id' => 'int',
            'product_name' => 'string',
            'variant_name' => 'string',
            'selling_unit' => 'string',
            'pieces_per_unit' => 'int',
            'price' => 'float',
            'status' => 'string',
        ];
    }

    /**
     * Format variant for display
     */
    public static function formatForDisplay(array $variant): string
    {
        return sprintf(
            '%s - %s (₱%.2f)',
            $variant['product_name'] ?? '',
            $variant['variant_name'] ?? '',
            $variant['price'] ?? 0
        );
    }

    /**
     * Enrich variant with display info
     */
    public static function enrichForDisplay(array $variant): array
    {
        $unit = $variant['selling_unit'] ?? 'piece';
        $pieces = $variant['pieces_per_unit'] ?? 1;

        $variant['unit_display'] = match ($unit) {
            'bundle' => "Bundle ({$pieces}pc)",
            'tray' => 'Tray',
            'half_tray' => 'Half Tray',
            default => 'Piece',
        };

        $variant['formula'] = SaleLineItemCalculator::getFormula($unit);

        return $variant;
    }
}
