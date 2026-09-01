<?php
/**
 * UserQueries - Centralized user database operations
 * Encapsulates all queries related to users (staff and admin)
 * Used by: login, user management, staff pages
 * @package SalesTrack\Database
 */

class UserQueries {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array {
        $sql = "SELECT id, name, email, role FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get user by email (for login)
     */
    public function getUserByEmail(string $email): ?array {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get all users
     */
    public function getAllUsers(): array {
        $sql = "SELECT id, name, email, role FROM users ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get all staff users (non-admin)
     */
    public function getAllStaff(): array {
        $sql = "SELECT id, name, email, role FROM users WHERE role = 'staff' ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get all admin users
     */
    public function getAllAdmins(): array {
        $sql = "SELECT id, name, email, role FROM users WHERE role = 'admin' ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Create new user
     */
    public function createUser(array $data): int {
        $sql = "INSERT INTO users (name, email, password, role)
                VALUES (:name, :email, :password, :role)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':role' => $data['role'] ?? 'staff'
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Update user
     */
    public function updateUser(int $userId, array $data): bool {
        $updates = [];
        $params = [':id' => $userId];
        
        if (isset($data['name'])) {
            $updates[] = 'name = :name';
            $params[':name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $updates[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        if (isset($data['password'])) {
            $updates[] = 'password = :password';
            $params[':password'] = $data['password'];
        }
        if (isset($data['role'])) {
            $updates[] = 'role = :role';
            $params[':role'] = $data['role'];
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Check if email already exists
     */
    public function emailExists(string $email): bool {
        $sql = "SELECT 1 FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Check if email exists for a different user (for updates)
     */
    public function emailExistsForOther(string $email, int $excludeUserId): bool {
        $sql = "SELECT 1 FROM users WHERE email = :email AND id != :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email, ':id' => $excludeUserId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Check if user exists
     */
    public function userExists(int $userId): bool {
        $sql = "SELECT 1 FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
