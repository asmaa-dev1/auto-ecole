<?php
require_once __DIR__ . '/../config/database.php';
// Ajoute ces fonctions hors de la classe AppFunctions:



global $conn;



// En haut du fichier functions.php, avant la classe
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

class AppFunctions {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        global $db;
        $this->conn = $db->getConnection();
    }

    // Dans la classe AppFunctions, ajoute ces méthodes:
public function getUserProfileImage($userId) {
    $stmt = $this->conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result && $result['profile_image']) {
        return '/assets/images/profiles/' . $result['profile_image'];
    }
    return 'https://ui-avatars.com/api/?name=User';
}

public function getUserFullName($userId) {
    $stmt = $this->conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        return $result['first_name'] . ' ' . $result['last_name'];
    }
    return 'User';
}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // User Management
    public function getUserById($id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM users WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUsersByRole($role) {
        $stmt = $this->conn->prepare("
            SELECT * FROM users 
            WHERE role = ? 
            AND status = 'active'
            ORDER BY first_name, last_name
        ");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Course Management
    public function getAllCourses($filters = []) {
        $sql = "SELECT c.*, 
                       lt.name as license_type_name,
                       lt.code as license_type_code,
                       COUNT(DISTINCT e.id) as total_enrollments,
                       AVG(r.rating) as average_rating
                FROM courses c
                LEFT JOIN license_types lt ON c.license_type_id = lt.id
                LEFT JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN course_ratings r ON c.id = r.course_id
                WHERE 1 = 1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['license_type'])) {
            $sql .= " AND lt.code = ?";
            $params[] = $filters['license_type'];
            $types .= "s";
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.name";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCourseById($id) {
        $stmt = $this->conn->prepare("
            SELECT c.*, 
                   lt.name as license_type_name,
                   lt.code as license_type_code,
                   COUNT(DISTINCT e.id) as total_enrollments,
                   AVG(r.rating) as average_rating
            FROM courses c
            LEFT JOIN license_types lt ON c.license_type_id = lt.id
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN course_ratings r ON c.id = r.course_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Enrollment Management
    public function getEnrollmentsByUser($userId, $status = null) {
        $sql = "SELECT e.*, 
                       c.name as course_name,
                       c.description as course_description,
                       u.first_name as instructor_first_name,
                       u.last_name as instructor_last_name,
                       u.profile_image as instructor_image,
                       lt.code as license_type_code
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                LEFT JOIN users u ON e.instructor_id = u.id 
                JOIN license_types lt ON c.license_type_id = lt.id
                WHERE e.candidate_id = ?";
        
        $params = [$userId];
        $types = "i";
        
        if ($status) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $sql .= " ORDER BY e.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Progress Tracking
    public function calculateProgress($enrollmentId) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_sessions,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
                SUM(CASE 
                    WHEN session_type = 'theory' AND status = 'completed' THEN 1 
                    ELSE 0 
                END) as completed_theory,
                SUM(CASE 
                    WHEN session_type = 'practice' AND status = 'completed' THEN 1 
                    ELSE 0 
                END) as completed_practice,
                COUNT(CASE WHEN session_type = 'theory' THEN 1 END) as total_theory,
                COUNT(CASE WHEN session_type = 'practice' THEN 1 END) as total_practice
            FROM sessions 
            WHERE enrollment_id = ?
        ");
        $stmt->bind_param("i", $enrollmentId);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        
        return [
            'total' => $data['total_sessions'] > 0 
                ? ($data['completed_sessions'] / $data['total_sessions']) * 100 
                : 0,
            'theory' => $data['total_theory'] > 0 
                ? ($data['completed_theory'] / $data['total_theory']) * 100 
                : 0,
            'practice' => $data['total_practice'] > 0 
                ? ($data['completed_practice'] / $data['total_practice']) * 100 
                : 0
        ];
    }
    

    // Utility Functions
    public function formatDate($date, $format = 'd/m/Y') {
        return date($format, strtotime($date));
    }

    public function formatMoney($amount) {
        return number_format($amount, 2, ',', ' ') . ' DH';
    }

    public function getStatusBadgeClass($status) {
        $classes = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'active' => 'bg-green-100 text-green-800',
            'completed' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'suspended' => 'bg-orange-100 text-orange-800',
            'expired' => 'bg-gray-100 text-gray-800'
        ];
        return $classes[$status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabel($status) {
        switch ($status) {
            case 'pending':
                return 'En attente';
            case 'active':
                return 'Actif';
            case 'completed':
                return 'Terminé';
            case 'cancelled':
                return 'Annulé';
            default:
                return ucfirst($status);
        }
    }

    // Session Management
    public function getUpcomingSessions($userId, $role = 'candidate', $limit = 5) {
        $sql = "
            SELECT s.*, 
                   c.name as course_name,
                   CASE 
                       WHEN ? = 'candidate' THEN CONCAT(u_inst.first_name, ' ', u_inst.last_name)
                       ELSE CONCAT(u_cand.first_name, ' ', u_cand.last_name)
                   END as other_party_name,
                   CASE 
                       WHEN ? = 'candidate' THEN u_inst.profile_image
                       ELSE u_cand.profile_image
                   END as other_party_image
            FROM sessions s
            JOIN enrollments e ON s.enrollment_id = e.id
            JOIN courses c ON e.course_id = c.id
            JOIN users u_inst ON e.instructor_id = u_inst.id
            JOIN users u_cand ON e.candidate_id = u_cand.id
            WHERE " . ($role === 'candidate' ? 'e.candidate_id' : 'e.instructor_id') . " = ?
            AND s.session_date >= CURDATE()
            AND s.status != 'cancelled'
            ORDER BY s.session_date ASC, s.start_time ASC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssii", $role, $role, $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Document Management
    public function getCourseDocuments($courseId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM course_documents 
            WHERE course_id = ? 
            ORDER BY document_type, title
        ");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getDocumentIcon($type) {
        $icons = [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'ppt' => 'fa-file-powerpoint',
            'pptx' => 'fa-file-powerpoint',
            'txt' => 'fa-file-lines',
            'zip' => 'fa-file-archive',
            'image' => 'fa-file-image',
            'video' => 'fa-file-video',
            'audio' => 'fa-file-audio'
        ];
        return $icons[$type] ?? 'fa-file';
    }

    // Payment Management
    public function getPaymentStatus($enrollmentId) {
        $stmt = $this->conn->prepare("
            SELECT 
                e.total_amount,
                COALESCE(SUM(p.amount), 0) as paid_amount
            FROM enrollments e
            LEFT JOIN payments p ON e.id = p.enrollment_id 
                AND p.status = 'completed'
            WHERE e.id = ?
            GROUP BY e.id, e.total_amount
        ");
        $stmt->bind_param("i", $enrollmentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) return null;
        
        return [
            'total' => $result['total_amount'],
            'paid' => $result['paid_amount'],
            'remaining' => $result['total_amount'] - $result['paid_amount'],
            'percentage' => ($result['paid_amount'] / $result['total_amount']) * 100
        ];
    }

    // Notification System
    public function createNotification($userId, $type, $message, $data = []) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                user_id, notification_type, message, data
            ) VALUES (?, ?, ?, ?)
        ");
        $dataJson = json_encode($data);
        $stmt->bind_param("isss", $userId, $type, $message, $dataJson);
        return $stmt->execute();
    }

    public function getUnreadNotifications($userId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            AND read_at IS NULL 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Statistics and Analytics
    public function getUserStatistics($userId, $role = 'candidate') {
        $stats = [];
        
        if ($role === 'candidate') {
            // Get candidate's course progress
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(DISTINCT e.id) as total_courses,
                    SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_courses,
                    COUNT(DISTINCT s.id) as total_sessions,
                    SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions
                FROM enrollments e
                LEFT JOIN sessions s ON e.id = s.enrollment_id
                WHERE e.candidate_id = ?
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stats['courses'] = $stmt->get_result()->fetch_assoc();
            
            // Get latest evaluation results
            $stmt = $this->conn->prepare("
                SELECT 
                    eval_type,
                    score,
                    MAX(evaluation_date) as latest_date
                FROM evaluations
                WHERE candidate_id = ?
                GROUP BY eval_type
                ORDER BY evaluation_date DESC
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stats['evaluations'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else if ($role === 'instructor') {
            // Get instructor's teaching statistics
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(DISTINCT e.candidate_id) as total_students,
                    COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.candidate_id END) as active_students,
                    COUNT(DISTINCT s.id) as total_sessions,
                    SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
                    AVG(r.rating) as average_rating
                FROM enrollments e
                LEFT JOIN sessions s ON e.id = s.enrollment_id
                LEFT JOIN instructor_ratings r ON e.instructor_id = r.instructor_id
                WHERE e.instructor_id = ?
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stats['teaching'] = $stmt->get_result()->fetch_assoc();
            
            // Get success rate
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(DISTINCT e.id) as total_completed,
                    COUNT(DISTINCT CASE WHEN eval.passed = 1 THEN e.id END) as passed
                FROM enrollments e
                JOIN evaluations eval ON e.id = eval.enrollment_id
                WHERE e.instructor_id = ? AND e.status = 'completed'
                AND eval.eval_type = 'final'
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stats['success_rate'] = $stmt->get_result()->fetch_assoc();
        }
        
        return $stats;
    }

    // Helper function for file size formatting
    public function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    // Calendar helper functions
    public function getWeekDates($date = null) {
        $date = $date ? new DateTime($date) : new DateTime();
        $date->modify('monday this week');
        
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $current = clone $date;
            $current->modify("+$i days");
            $dates[] = $current;
        }
        
        return $dates;
    }

    public function getTimeSlots($startHour = 8, $endHour = 18, $interval = 30) {
        $slots = [];
        $startTime = new DateTime("$startHour:00");
        $endTime = new DateTime("$endHour:00");
        
        while ($startTime <= $endTime) {
            $slots[] = $startTime->format('H:i');
            $startTime->modify("+$interval minutes");
        }
        
        return $slots;
    }

    public function getReadableRole($roleCode) {
        $roles = [
            'admin' => 'Administrateur',
            'instructor' => 'Instructeur',
            'candidate' => 'Candidat',
            'assistant' => 'Assistant'
        ];
        return $roles[$roleCode] ?? $roleCode;
    }
}

// Initialize functions instance
$functions = AppFunctions::getInstance();

// Global helper functions
function formatDate($date, $format = 'd/m/Y') {
    return AppFunctions::getInstance()->formatDate($date, $format);
}

function getStatusBadgeClass($status) {
    return AppFunctions::getInstance()->getStatusBadgeClass($status);
}

function getStatusLabel($status) {
    return AppFunctions::getInstance()->getStatusLabel($status);
}

function getReadableRole($roleCode) {
    return AppFunctions::getInstance()->getReadableRole($roleCode);
}

if (!isset($db)) {
    $db = new DatabaseConnection();
}


// Initialize functions instance
$functions = AppFunctions::getInstance();