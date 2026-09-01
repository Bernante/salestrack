<?php
/**
 * DatabaseQueryLogger - Query execution logging and performance tracking
 * Wraps PDO queries to provide audit trail, performance metrics, and debugging
 * @package SalesTrack\Database
 */

class DatabaseQueryLogger {
    private PDO $db;
    private string $logPath;
    private bool $enabled = true;
    private int $slowQueryThreshold = 100;
    
    public function __construct(PDO $db, string $logPath = '') {
        $this->db = $db;
        $this->logPath = $logPath ?: ini_get('error_log');
        if (!$this->logPath) {
            $this->logPath = sys_get_temp_dir() . '/salestrack_db.log';
        }
    }
    
    public function logSelect(string $sql, array $params = []): array {
        $startTime = microtime(true);
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $duration = (int)(($startTime - microtime(true)) * -1000);
            $this->log($sql, $params, 'SELECT', count($result), $duration);
            return $result;
        } catch (Exception $e) {
            $this->logError($sql, $params, 'SELECT', $e);
            throw $e;
        }
    }
    
    public function logInsert(string $sql, array $params = []): int {
        $startTime = microtime(true);
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $insertId = (int)$this->db->lastInsertId();
            $duration = (int)(($startTime - microtime(true)) * -1000);
            $this->log($sql, $params, 'INSERT', 1, $duration);
            return $insertId;
        } catch (Exception $e) {
            $this->logError($sql, $params, 'INSERT', $e);
            throw $e;
        }
    }
    
    public function logUpdate(string $sql, array $params = []): int {
        $startTime = microtime(true);
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            $duration = (int)(($startTime - microtime(true)) * -1000);
            $this->log($sql, $params, 'UPDATE', $affected, $duration);
            return $affected;
        } catch (Exception $e) {
            $this->logError($sql, $params, 'UPDATE', $e);
            throw $e;
        }
    }
    
    public function logDelete(string $sql, array $params = []): int {
        $startTime = microtime(true);
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            $duration = (int)(($startTime - microtime(true)) * -1000);
            $this->log($sql, $params, 'DELETE', $affected, $duration);
            return $affected;
        } catch (Exception $e) {
            $this->logError($sql, $params, 'DELETE', $e);
            throw $e;
        }
    }
    
    public function enable(): void { $this->enabled = true; }
    public function disable(): void { $this->enabled = false; }
    public function setSlowQueryThreshold(int $ms): void { $this->slowQueryThreshold = $ms; }
    
    private function log(string $sql, array $params, string $type, int $count, int $duration): void {
        if (!$this->enabled) return;
        $user = $_SESSION['user_id'] ?? 'unknown';
        $slow = $duration > $this->slowQueryThreshold ? '[SLOW] ' : '';
        $msg = "$slow$type | User: $user | ${duration}ms | Rows: $count | " . substr($sql, 0, 80);
        error_log($msg);
    }
    
    private function logError(string $sql, array $params, string $type, Exception $e): void {
        if (!$this->enabled) return;
        $msg = "ERROR | $type | " . $e->getMessage() . " | " . substr($sql, 0, 80);
        error_log($msg);
    }
}
