<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Get database connection
$db = new DatabaseConnection();
$conn = $db->getConnection();

// Initialize Auth and Functions
$auth = Auth::getInstance();
$functions = AppFunctions::getInstance();

// Ensure user is logged in and is an instructor
$auth->requireLogin();
$auth->checkRole('instructor');

// Get instructor's information
$instructor = $functions->getUserById($_SESSION['user_id']);

// Get active students count
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT e.candidate_id) as total_students
    FROM enrollments e
    WHERE e.instructor_id = ? 
    AND e.status = 'active'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$activeStudents = $stmt->get_result()->fetch_assoc()['total_students'];

// Get today's sessions
$stmt = $conn->prepare("
    SELECT s.*, 
           c.name as course_name,
           u.first_name as student_first_name,
           u.last_name as student_last_name
    FROM sessions s
    JOIN enrollments e ON s.enrollment_id = e.id
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON e.candidate_id = u.id
    WHERE e.instructor_id = ?
    AND s.session_date = CURDATE()
    AND s.status != 'cancelled'
    ORDER BY s.start_time ASC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$todaySessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent student progress
$stmt = $conn->prepare("
    SELECT 
        u.first_name,
        u.last_name,
        c.name as course_name,
        e.id as enrollment_id,
        e.start_date,
        COUNT(DISTINCT s.id) as total_sessions,
        SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions
    FROM enrollments e
    JOIN users u ON e.candidate_id = u.id
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN sessions s ON e.id = s.enrollment_id
    WHERE e.instructor_id = ?
    AND e.status = 'active'
    GROUP BY e.id
    ORDER BY e.start_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$studentProgress = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/instructor-sidebar.php'; ?>

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
                <div class="text-right">
                    <div class="text-sm text-gray-600">Étudiants actifs</div>
                    <div class="text-3xl font-bold text-primary"><?php echo $activeStudents; ?></div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Today's Sessions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Sessions d'aujourd'hui</h2>
                    <span class="text-2xl font-bold text-primary"><?php echo count($todaySessions); ?></span>
                </div>
                <?php if (!empty($todaySessions)): ?>
                    <div class="space-y-3">
                        <?php foreach ($todaySessions as $session): ?>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                    <i class="fas <?php echo $session['session_type'] === 'theory' ? 'fa-book' : 'fa-car'; ?> text-primary"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($session['student_first_name'] . ' ' . $session['student_last_name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        <?php echo substr($session['start_time'], 0, 5); ?> - 
                                        <?php echo substr($session['end_time'], 0, 5); ?>
                                    </p>
                                </div>
                                <div class="ml-auto">
                                    <a href="/auto-ecole/pages/instructor/session.php?id=<?php echo $session['id']; ?>" 
                                       class="text-primary hover:text-accent">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600 text-center py-4">
                        Aucune session programmée aujourd'hui
                    </p>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h2>
                <div class="grid grid-cols-2 gap-4">
                    <a href="/auto-ecole/pages/instructor/schedule-session.php" 
                       class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-calendar-plus text-primary"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Programmer une session</span>
                    </a>
                    <a href="/auto-ecole/pages/instructor/students.php" 
                       class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Voir les étudiants</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Student Progress -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Progression des étudiants</h2>
                <a href="/auto-ecole/pages/instructor/students.php" 
                   class="text-sm text-primary hover:text-accent">
                    Voir tous les étudiants
                </a>
            </div>
            <?php if (!empty($studentProgress)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Étudiant
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cours
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date de début
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Progression
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($studentProgress as $progress): ?>
                                <?php 
                                    $progressPercentage = $progress['total_sessions'] > 0 
                                        ? ($progress['completed_sessions'] / $progress['total_sessions']) * 100 
                                        : 0;
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($progress['first_name'] . ' ' . $progress['last_name']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($progress['course_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo formatDate($progress['start_date']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-primary rounded-full h-2.5" 
                                                 style="width: <?php echo $progressPercentage; ?>%">
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">
                                            <?php echo $progress['completed_sessions']; ?>/<?php echo $progress['total_sessions']; ?> sessions
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="/auto-ecole/pages/instructor/student-detail.php?enrollment_id=<?php echo $progress['enrollment_id']; ?>" 
                                           class="text-primary hover:text-accent">
                                            Voir détails
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600 text-center py-4">
                    Aucun étudiant actif pour le moment
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>