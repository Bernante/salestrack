<?php
/**
 * ProductQueries - Centralized product database operations
 * Encapsulates all queries related to products and variants
 * Used by: product pages, admin, product create/edit
 * @package SalesTrack\Database
 */

class ProductQueries {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Get all products
     */
    public function getAllProducts(): array {
        $sql = "SELECT * FROM products ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get product by ID
     */
    public function getProductById(int $productId): ?array {
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get product with all variants
     */
    public function getProductWithVariants(int $productId): ?array {
        $product = $this->getProductById($productId);
        if (!$product) return null;
        
        $product['variants'] = $this->getVariantsByProductId($productId);
        return $product;
    }
    
    /**
     * Get all variants for a product
     */
    public function getVariantsByProductId(int $productId): array {
        $sql = "SELECT * FROM product_variants WHERE product_id = :pid ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get variant by ID
     */
    public function getVariantById(int $variantId): ?array {
        $sql = "SELECT * FROM product_variants WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $variantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Create product
     */
    public function createProduct(array $data): int {
        $sql = "INSERT INTO products (name, image) VALUES (:name, :image)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':image' => $data['image'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Create product variant
     */
    public function createVariant(array $data): int {
        $sql = "INSERT INTO product_variants (product_id, variant_name, quantity, 
                selling_unit, pieces_per_unit, price)
                VALUES (:pid, :name, :qty, :unit, :pieces, :price)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':pid' => $data['product_id'],
            ':name' => $data['variant_name'],
            ':qty' => $data['quantity'] ?? 1,
            ':unit' => $data['selling_unit'] ?? 'piece',
            ':pieces' => $data['pieces_per_unit'] ?? 1,
            ':price' => $data['price'] ?? 0
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Update product
     */
    public function updateProduct(int $productId, array $data): bool {
        $updates = [];
        $params = [':id' => $productId];
        
        if (isset($data['name'])) {
            $updates[] = 'name = :name';
            $params[':name'] = $data['name'];
        }
        if (isset($data['image'])) {
            $updates[] = 'image = :image';
            $params[':image'] = $data['image'];
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Update variant
     */
    public function updateVariant(int $variantId, array $data): bool {
        $updates = [];
        $params = [':id' => $variantId];
        
        if (isset($data['variant_name'])) {
            $updates[] = 'variant_name = :name';
            $params[':name'] = $data['variant_name'];
        }
        if (isset($data['price'])) {
            $updates[] = 'price = :price';
            $params[':price'] = $data['price'];
        }
        if (isset($data['selling_unit'])) {
            $updates[] = 'selling_unit = :unit';
            $params[':unit'] = $data['selling_unit'];
        }
        if (isset($data['pieces_per_unit'])) {
            $updates[] = 'pieces_per_unit = :pieces';
            $params[':pieces'] = $data['pieces_per_unit'];
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE product_variants SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}

