<?php
/**
 * SaleQueries - Centralized sale database operations
 * Encapsulates all queries related to sales and sale items
 * Used by: save-sale.php, cancel-sale.php, sales pages
 * @package SalesTrack\Database
 */

class SaleQueries {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Get sale by ID with staff name
     */
    public function getSaleById(int $saleId): ?array {
        $sql = "SELECT s.*, u.name AS staff_name FROM sales s 
                JOIN users u ON s.user_id = u.id WHERE s.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $saleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get sale by transaction number
     */
    public function getSaleByTransactionNumber(string $txnNumber): ?array {
        $sql = "SELECT * FROM sales WHERE transaction_number = :txn LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':txn' => $txnNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get all sale items for a sale with product details
     */
    public function getSaleItems(int $saleId): array {
        $sql = "SELECT si.*, pv.variant_name, p.name AS product_name
                FROM sale_items si
                JOIN product_variants pv ON si.product_variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                WHERE si.sale_id = :sale_id ORDER BY si.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sale_id' => $saleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get all sales with optional filtering and search
     */
    public function getAllSales(string $search = ''): array {
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(s.transaction_number LIKE :search OR u.name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT s.*, u.name AS staff_name FROM sales s
                JOIN users u ON s.user_id = u.id
                $whereClause ORDER BY s.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get sales by date range
     */
    public function getSalesByDateRange(string $dateFrom, string $dateTo): array {
        $sql = "SELECT s.*, u.name AS staff_name FROM sales s
                JOIN users u ON s.user_id = u.id
                WHERE DATE(s.created_at) >= :from AND DATE(s.created_at) <= :to
                ORDER BY s.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Create a new sale (returns sale ID)
     */
    public function createSale(array $data): int {
        $sql = "INSERT INTO sales (transaction_number, user_id, sale_date, total_amount, 
                amount_paid, change_amount, payment_status)
                VALUES (:txn, :user_id, :date, :total, :paid, :change, :payment_status)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':txn' => $data['transaction_number'],
            ':user_id' => $data['user_id'],
            ':date' => $data['sale_date'],
            ':total' => $data['total_amount'],
            ':paid' => $data['amount_paid'],
            ':change' => $data['change_amount'],
            ':payment_status' => 'paid'
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Add item to sale
     */
    public function addSaleItem(int $saleId, array $itemData): int {
        $sql = "INSERT INTO sale_items (sale_id, product_variant_id, quantity, unit_price, subtotal)
                VALUES (:sale_id, :variant_id, :qty, :price, :subtotal)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sale_id' => $saleId,
            ':variant_id' => $itemData['product_variant_id'],
            ':qty' => $itemData['quantity'],
            ':price' => $itemData['unit_price'],
            ':subtotal' => $itemData['subtotal']
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Get variants by IDs for sale validation
     */
    public function getVariantsByIds(array $variantIds): array {
        if (empty($variantIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $sql = "SELECT pv.id, pv.product_id, pv.variant_name, pv.price, 
                pv.selling_unit, pv.pieces_per_unit,
                p.name AS product_name
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                WHERE pv.id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($variantIds);
        
        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[$row['id']] = $row;
        }
        return $results;
    }
}
