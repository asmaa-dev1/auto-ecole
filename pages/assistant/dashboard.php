<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();


$currentPage = basename($_SERVER['PHP_SELF']);

// Get pending count for sidebar
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'candidate' AND status = 'pending'");
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
// Initialize Auth
$auth = Auth::getInstance();
$auth->requireLogin();
$auth->checkRole('assistant');

// Get assistant info - FIRST QUERY
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");  
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$assistant = $stmt->get_result()->fetch_assoc();
$stmt->close(); // Close the statement

// Get pending candidates count - SECOND QUERY
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE role = 'candidate' 
    AND status = 'pending'
");
$stmt->execute();
$pendingCandidates = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close(); // Close the statement

// Get today's scheduled sessions - THIRD QUERY
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM sessions s
    JOIN enrollments e ON s.enrollment_id = e.id
    WHERE s.session_date = CURDATE()
    AND s.status = 'scheduled'
"); 
$stmt->execute();
$todaySessions = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close(); // Close the statement

// Get recent activities - FOURTH QUERY
$stmt = $conn->prepare("
    SELECT a.*, u.first_name, u.last_name, u.email
    FROM activity_logs a
    JOIN users u ON a.user_id = u.id
    WHERE a.created_by = ?
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recentActivities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close(); // Close the statement

// Get recent registrations - FIFTH QUERY
$stmt = $conn->prepare("
    SELECT u.*, c.name as course_name
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.candidate_id
    LEFT JOIN courses c ON e.course_id = c.id
    WHERE u.role = 'candidate'
    ORDER BY u.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentRegistrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close(); // Close the statement
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Include sidebar first -->
    <?php include '../../includes/assistant-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Bienvenue, <?php echo htmlspecialchars($assistant['first_name']); ?>!
                    </h1>
                    <p class="text-gray-600 mt-1">
                        <?php echo date('l, d F Y'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Pending Candidates -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Candidats en attente</p>
                        <p class="text-3xl font-bold text-primary"><?php echo $pendingCandidates; ?></p>
                    </div>
                    <div class="bg-primary/10 rounded-full p-3">
                        <i class="fas fa-user-clock text-xl text-primary"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="/pages/assistant/candidates.php?status=pending" 
                       class="text-sm text-primary hover:text-accent">
                        Voir les détails →
                    </a>
                </div>
            </div>

            <!-- Today's Sessions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Sessions aujourd'hui</p>
                        <p class="text-3xl font-bold text-primary"><?php echo $todaySessions; ?></p>
                    </div>
                    <div class="bg-primary/10 rounded-full p-3">
                        <i class="fas fa-calendar-day text-xl text-primary"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="/pages/assistant/management.php" 
                       class="text-sm text-primary hover:text-accent">
                        Gérer les sessions →
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-600 mb-4">Actions rapides</h3>
                <div class="grid grid-cols-2 gap-4">
                    <a href="/pages/assistant/candidates.php?action=new" 
                       class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-user-plus text-primary mb-2"></i>
                        <span class="text-sm font-medium">Nouveau candidat</span>
                    </a>
                    <a href="/pages/assistant/management.php?action=schedule" 
                       class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-calendar-plus text-primary mb-2"></i>
                        <span class="text-sm font-medium">Planifier session</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Registrations Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Activités récentes</h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($recentActivities)): ?>
                        <div class="space-y-4">
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                                            <i class="fas fa-clipboard-list text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['action']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo formatDate($activity['created_at']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center">Aucune activité récente</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Registrations -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Inscriptions récentes</h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($recentRegistrations)): ?>
                        <div class="space-y-4">
                            <?php foreach ($recentRegistrations as $registration): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo $registration['course_name'] ? htmlspecialchars($registration['course_name']) : 'Pas encore inscrit au cours'; ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo getStatusBadgeClass($registration['status']); ?>">
                                        <?php echo getStatusLabel($registration['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center">Aucune inscription récente</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ajoute juste avant la dernière </div> dans dashboard.php -->
<?php include '../../includes/assistant-sidebar.php'; ?>