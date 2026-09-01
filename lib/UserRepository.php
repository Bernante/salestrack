<?php
/**
 * UserRepository - Data access for users (staff and admin)
 * 
 * Encapsulates all user-related database queries
 * Handles user authentication, creation, and management
 * 
 * @package SalesTrack\Repositories
 */

require_once __DIR__ . '/BaseRepository.php';

class UserRepository extends BaseRepository {
    
    protected string $table = 'users';
    
    /**
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->fetchOne($sql, [':id' => $id]);
    }
    
    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->fetchOne($sql, [':email' => $email]);
    }
    
    /**
     * Get all users
     * 
     * @return array Array of users
     */
    public function findAll(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        return $this->fetchAll($sql);
    }
    
    /**
     * Get users by role
     * 
     * @param string $role User role (admin, staff)
     * @return array Array of users with specified role
     */
    public function findByRole(string $role): array {
        $sql = "SELECT * FROM {$this->table} WHERE role = :role ORDER BY name ASC";
        return $this->fetchAll($sql, [':role' => $role]);
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data (name, email, password, role)
     * @return int User ID
     * @throws Exception
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO {$this->table} (name, email, password, role)
            VALUES (:name, :email, :password, :role)
        ";
        
        $params = [
            ':name'     => $data['name'] ?? '',
            ':email'    => $data['email'] ?? '',
            ':password' => $data['password'] ?? '',
            ':role'     => $data['role'] ?? 'staff'
        ];
        
        return $this->insert($sql, $params);
    }
    
    /**
     * Update user
     * 
     * @param int $id User ID
     * @param array $data Fields to update (name, email, password, role)
     * @return bool True if successful
     */
    public function update(int $id, array $data): bool {
        $updates = [];
        $params = [':id' => $id];
        
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
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = :id";
        
        try {
            parent::update($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log('UserRepository::update error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user exists by ID
     * 
     * @param int $id User ID
     * @return bool True if user exists
     */
    public function exists(int $id): bool {
        $sql = "SELECT 1 FROM {$this->table} WHERE id = :id LIMIT 1";
        $result = $this->fetchOne($sql, [':id' => $id]);
        return $result !== null;
    }
    
    /**
     * Check if email exists
     * 
     * @param string $email Email address
     * @return bool True if email exists
     */
    public function emailExists(string $email): bool {
        $sql = "SELECT 1 FROM {$this->table} WHERE email = :email LIMIT 1";
        $result = $this->fetchOne($sql, [':email' => $email]);
        return $result !== null;
    }
    
    /**
     * Check if email exists for different user (useful for updates)
     * 
     * @param string $email Email address
     * @param int $excludeUserId User ID to exclude from check
     * @return bool True if email exists for different user
     */
    public function emailExistsForOtherUser(string $email, int $excludeUserId): bool {
        $sql = "SELECT 1 FROM {$this->table} WHERE email = :email AND id != :id LIMIT 1";
        $result = $this->fetchOne($sql, [':email' => $email, ':id' => $excludeUserId]);
        return $result !== null;
    }
}
