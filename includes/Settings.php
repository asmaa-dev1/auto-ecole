<?php
require_once __DIR__ . '/../config/database.php';

class Settings {
    private static $instance = null;
    private $conn;
    private $settings = [];
    
    private function __construct() {
        // Get database connection
        $db = DatabaseConnection::getInstance();
        $this->conn = $db->getConnection();
        $this->loadSettings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadSettings() {
        $stmt = $this->conn->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    
    public function set($key, $value) {
        $stmt = $this->conn->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        
        $stmt->bind_param("sss", $key, $value, $value);
        
        if ($stmt->execute()) {
            $this->settings[$key] = $value;
            return true;
        }
        
        return false;
    }
    
    public function update($settings) {
        $success = true;
        $this->conn->begin_transaction();
        
        try {
            foreach ($settings as $key => $value) {
                if (!$this->set($key, $value)) {
                    throw new Exception("Failed to update setting: $key");
                }
            }
            
            $this->conn->commit();
            $this->loadSettings(); // Reload settings after update
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    public function getAll() {
        return $this->settings;
    }
}