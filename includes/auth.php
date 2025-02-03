<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


class Auth {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        global $db;
        $this->conn = $db->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /auto-ecole/pages/auth/login.php');
            exit();
        }
    }

    public function checkRole($requiredRole) {
        if (!$this->isLoggedIn() || $_SESSION['role'] !== $requiredRole) {
            $this->logSecurityEvent('Unauthorized access attempt', [
                'required_role' => $requiredRole,
                'user_role' => $_SESSION['role'] ?? 'none'
            ]);
            header('Location: /403.php');
            exit();
        }
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("
            SELECT id, password, role, status, failed_attempts, locked_until 
            FROM users 
            WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'message' => 'Account is temporarily locked. Please try again later.'];
        }

        if (!password_verify($password, $user['password'])) {
            // Increment failed attempts
            $this->updateFailedAttempts($user['id'], $user['failed_attempts'] + 1);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }

        // Reset failed attempts on successful login
        $this->updateFailedAttempts($user['id'], 0);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // Generate and store session token
        $_SESSION['token'] = bin2hex(random_bytes(32));
                
        return ['success' => true, 'role' => $user['role']];
    }

    public function logout() {
        
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    private function updateFailedAttempts($userId, $attempts) {
        $lockedUntil = null;
        if ($attempts >= 5) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        }

        $stmt = $this->conn->prepare("
            UPDATE users 
            SET failed_attempts = ?, 
                locked_until = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $attempts, $lockedUntil, $userId);
        $stmt->execute();
    }

    private function logSecurityEvent($event, $details) {
        $stmt = $this->conn->prepare("
            INSERT INTO security_logs (
                user_id, event_type, details, ip_address
            ) VALUES (?, ?, ?, ?)
        ");
        $userId = $_SESSION['user_id'] ?? null;
        $detailsJson = json_encode($details);
        $stmt->bind_param("isss", 
            $userId, 
            $event, 
            $detailsJson,
            $_SERVER['REMOTE_ADDR']
        );
        $stmt->execute();
    }

    public function generatePasswordResetToken($email) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->conn->prepare("
            INSERT INTO password_resets (
                email, token, expires_at
            ) VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $email, $token, $expires);
        
        if ($stmt->execute()) {
            return $token;
        }
        return false;
    }

    public function verifyPasswordResetToken($token) {
        $stmt = $this->conn->prepare("
            SELECT email, expires_at 
            FROM password_resets 
            WHERE token = ? AND used = 0 
            AND expires_at > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function resetPassword($token, $newPassword) {
        $tokenData = $this->verifyPasswordResetToken($token);
        if (!$tokenData) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $this->conn->begin_transaction();
        try {
            // Update password
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET password = ?, 
                    failed_attempts = 0,
                    locked_until = NULL
                WHERE email = ?
            ");
            $stmt->bind_param("ss", $hashedPassword, $tokenData['email']);
            $stmt->execute();

            // Mark token as used
            $stmt = $this->conn->prepare("
                UPDATE password_resets 
                SET used = 1 
                WHERE token = ?
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}

// Security helper functions
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $special   = preg_match('@[^\w]@', $password);

    return strlen($password) >= 8 && $uppercase && $lowercase && $number && $special;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function validatePhoneNumber($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

// Initialize auth instance
$auth = Auth::getInstance();

// Initialize database connection
$db = new DatabaseConnection();

// Initialize auth instance
$auth = Auth::getInstance();