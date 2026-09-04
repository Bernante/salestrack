<?php
/**
 * SaleLineItemCalculator: Centralized Sale Line Item Calculation
 * 
 * Encapsulates all pricing formulas - single source of truth.
 * Server-side calculation authority (client uses for preview only).
 */

class SaleLineItemCalculator
{
    private const BUNDLE_UNITS = ['bundle'];

    /**
     * Calculate subtotal for a line item
     * 
     * Bundle: (quantity ÷ pieces_per_unit) × unit_price
     * Other: quantity × unit_price
     */
    public static function calculateSubtotal(
        float $quantity,
        float $unitPrice,
        string $sellingUnit,
        ?int $piecesPerUnit = null
    ): float {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }
        if ($unitPrice < 0) {
            throw new InvalidArgumentException('Unit price cannot be negative');
        }

        $sellingUnit = strtolower(trim($sellingUnit));
        if (empty($sellingUnit)) {
            throw new InvalidArgumentException('Selling unit cannot be empty');
        }

        // Bundle pricing
        if (in_array($sellingUnit, self::BUNDLE_UNITS, true)) {
            if ($piecesPerUnit === null || $piecesPerUnit <= 0) {
                throw new InvalidArgumentException(
                    'Bundle requires valid pieces_per_unit (> 0)'
                );
            }
            $subtotal = ($quantity / $piecesPerUnit) * $unitPrice;
        } else if ($sellingUnit === 'half_tray') {
            $subtotal = ($quantity / 15) * $unitPrice;
        } else if ($sellingUnit === 'tray') {
            $subtotal = ($quantity / 30) * $unitPrice;
        } else {
            $subtotal = $quantity * $unitPrice;
        }

        return round($subtotal, 2);
    }

    /**
     * Calculate total from multiple line items
     */
    public static function calculateSaleTotal(array $items): float
    {
        if (!is_array($items)) {
            throw new InvalidArgumentException('Items must be an array');
        }

        $total = 0.00;

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException(
                    "Item at index $index must be an array"
                );
            }

            $quantity = $item['quantity'] ?? $item['quantity_units'] ?? null;
            $unitPrice = $item['unit_price'] ?? $item['price'] ?? null;
            $sellingUnit = $item['selling_unit'] ?? null;
            $piecesPerUnit = $item['pieces_per_unit'] ?? null;

            if ($quantity === null) {
                throw new InvalidArgumentException(
                    "Item at index $index missing quantity"
                );
            }

            if ($unitPrice === null) {
                throw new InvalidArgumentException(
                    "Item at index $index missing unit_price"
                );
            }

            if ($sellingUnit === null) {
                throw new InvalidArgumentException(
                    "Item at index $index missing selling_unit"
                );
            }

            $subtotal = self::calculateSubtotal(
                floatval($quantity),
                floatval($unitPrice),
                strval($sellingUnit),
                $piecesPerUnit !== null ? intval($piecesPerUnit) : null
            );

            $total += $subtotal;
        }

        return round($total, 2);
    }

    /**
     * Validate that amount paid is sufficient
     */
    public static function validatePayment(
        float $amountPaid,
        float $totalAmount
    ): bool {
        return $amountPaid >= $totalAmount;
    }

    /**
     * Calculate change amount
     */
    public static function calculateChange(
        float $amountPaid,
        float $totalAmount
    ): float {
        if ($amountPaid < 0) {
            throw new InvalidArgumentException('Amount paid cannot be negative');
        }

        if ($totalAmount < 0) {
            throw new InvalidArgumentException('Total amount cannot be negative');
        }

        if ($amountPaid < $totalAmount) {
            throw new InvalidArgumentException(
                'Amount paid is insufficient'
            );
        }

        return round($amountPaid - $totalAmount, 2);
    }

    /**
     * Get supported selling units
     */
    public static function getSupportedUnits(): array
    {
        return ['piece', 'bundle', 'tray', 'half_tray'];
    }

    /**
     * Check if unit is bundle type
     */
    public static function isBundleUnit(string $sellingUnit): bool
    {
        return in_array(strtolower($sellingUnit), self::BUNDLE_UNITS, true);
    }

    /**
     * Get pricing formula for display
     */
    public static function getFormula(string $sellingUnit): string
    {
        if (self::isBundleUnit($sellingUnit)) {
            return '(quantity ÷ pieces_per_unit) × unit_price';
        }
        return 'quantity × unit_price';
    }
}
