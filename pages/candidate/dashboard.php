<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Initialize Auth
$auth = Auth::getInstance();
$auth->requireLogin();
$auth->checkRole('candidate');

// Get user info
$user = $functions->getUserById($_SESSION['user_id']); // Use $functions->getUserById()

// Get enrollments
$enrollments = $functions->getEnrollmentsByUser($_SESSION['user_id']); // Use $functions->getEnrollmentsByUser()

// Get current course progress
$activeEnrollment = null;
foreach ($enrollments as $enrollment) {
    if ($enrollment['status'] === 'active') {
        $activeEnrollment = $enrollment;
        break;
    }
}

// Get upcoming sessions
$stmt = $conn->prepare("
    SELECT s.*, c.name as course_name 
    FROM sessions s 
    JOIN enrollments e ON s.enrollment_id = e.id 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.candidate_id = ? 
    AND s.session_date >= CURDATE() 
    AND s.status = 'scheduled' 
    ORDER BY s.session_date ASC, s.start_time ASC 
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$upcomingSessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <nav class="fixed top-0 left-0 w-64 h-full bg-white shadow-lg pt-20">
        <div class="px-4 py-6">
            <div class="flex items-center mb-6">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'] . ' ' . $user['last_name']); ?>" 
                     alt="Profile" 
                     class="w-12 h-12 rounded-full">
                <div class="ml-3">
                    <p class="font-medium text-gray-800">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </p>
                    <p class="text-sm text-gray-500">Candidat</p>
                </div>
            </div>

            <ul class="space-y-2">
                <li>
                    <a href="/auto-ecole/pages/candidate/dashboard.php" 
                       class="flex items-center px-4 py-2 text-gray-700 bg-gray-100 rounded-lg">
                        <i class="fas fa-home mr-3"></i>
                        Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="/auto-ecole/pages/candidate/courses.php" 
                       class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-book mr-3"></i>
                        Mes cours
                    </a>
                </li>
                <li>
                    <a href="/auto-ecole/pages/candidate/schedule.php" 
                       class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-calendar mr-3"></i>
                        Planning
                    </a>
                </li>
                <li>
                    <a href="/auto-ecole/pages/candidate/profile.php" 
                       class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-user mr-3"></i>
                        Profil
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <!-- Progress Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Progression</h3>
                    <?php if ($activeEnrollment): ?>
                        <span class="px-2 py-1 text-sm text-white bg-green-500 rounded-full">
                            En cours
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($activeEnrollment): ?>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span><?php echo htmlspecialchars($activeEnrollment['course_name']); ?></span>
                        </div>
                        </div>
                    </div>

                <?php else: ?>
                    <p class="text-sm text-gray-600">
                        Aucun cours actif. Inscrivez-vous à un cours pour commencer.
                    </p>
                    <a href="/auto-ecole/pages/candidate/courses.php" 
                       class="mt-4 inline-block px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors text-sm">
                        Voir les cours
                    </a>
                <?php endif; ?>
            </div>

            <!-- Stats Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistiques</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Sessions Complétées</p>
                        <p class="text-2xl font-bold text-primary">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT COUNT(*) as count 
                                FROM sessions s 
                                JOIN enrollments e ON s.enrollment_id = e.id 
                                WHERE e.candidate_id = ? AND s.status = 'completed'
                            ");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            echo $stmt->get_result()->fetch_assoc()['count'];
                            ?>
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Heures de Pratique</p>
                        <p class="text-2xl font-bold text-primary">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT COALESCE(SUM(
                                    TIMESTAMPDIFF(HOUR, s.start_time, s.end_time)
                                ), 0) as hours
                                FROM sessions s 
                                JOIN enrollments e ON s.enrollment_id = e.id 
                                WHERE e.candidate_id = ? AND s.status = 'completed'
                            ");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            echo $stmt->get_result()->fetch_assoc()['hours'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Next Session Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Prochaine Session</h3>
                <?php if (!empty($upcomingSessions)): ?>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                    <i class="fas <?php echo $upcomingSessions[0]['session_type'] === 'theory' ? 'fa-book' : 'fa-car'; ?> text-primary"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-800">
                                    <?php echo htmlspecialchars($upcomingSessions[0]['course_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <?php echo formatDate($upcomingSessions[0]['session_date']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <?php echo substr($upcomingSessions[0]['start_time'], 0, 5); ?> - 
                                    <?php echo substr($upcomingSessions[0]['end_time'], 0, 5); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-600">
                        Aucune session programmée.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Sessions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sessions à venir</h3>
            <?php if (!empty($upcomingSessions)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Heure
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cours
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($upcomingSessions as $session): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatDate($session['session_date']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo substr($session['start_time'], 0, 5); ?> - 
                                        <?php echo substr($session['end_time'], 0, 5); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($session['course_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $session['session_type'] === 'theory' 
                                                ? 'bg-blue-100 text-blue-800' 
                                                : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo $session['session_type'] === 'theory' ? 'Théorie' : 'Pratique'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="/auto-ecole/pages/candidate/session.php?id=<?php echo $session['id']; ?>" 
                                           class="text-primary hover:text-accent">
                                            Voir détails
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <a href="/auto-ecole/pages/candidate/schedule.php" 
                       class="text-sm font-medium text-primary hover:text-accent">
                        Voir tout le planning →
                    </a>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-600">
                    Aucune session programmée pour le moment.
                </p>
            <?php endif; ?>
        </div>

        <!-- Recent Activities -->
        <div class="mt-6 bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Activités récentes</h3>
            <div class="space-y-4">
                <?php
                $stmt = $conn->prepare("
                    SELECT 
                        'session' as type,
                        s.session_date as date,
                        s.status,
                        c.name as course_name,
                        s.session_type
                    FROM sessions s
                    JOIN enrollments e ON s.enrollment_id = e.id
                    JOIN courses c ON e.course_id = c.id
                    WHERE e.candidate_id = ?
                    UNION ALL
                    SELECT 
                        'payment' as type,
                        p.payment_date as date,
                        p.status,
                        c.name as course_name,
                        NULL as session_type
                    FROM payments p
                    JOIN enrollments e ON p.enrollment_id = e.id
                    JOIN courses c ON e.course_id = c.id
                    WHERE e.candidate_id = ?
                    ORDER BY date DESC
                    LIMIT 5
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
                $stmt->execute();
                $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                foreach ($activities as $activity): ?>
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center 
                            <?php echo $activity['type'] === 'session' ? 'bg-blue-100' : 'bg-green-100'; ?>">
                            <i class="fas <?php echo $activity['type'] === 'session' ? 'fa-calendar' : 'fa-money-bill'; ?> 
                                <?php echo $activity['type'] === 'session' ? 'text-blue-500' : 'text-green-500'; ?>">
                            </i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">
                                <?php
                                if ($activity['type'] === 'session') {
                                    echo $activity['session_type'] === 'theory' ? 'Session théorique' : 'Session pratique';
                                } else {
                                    echo 'Paiement';
                                }
                                ?> - <?php echo htmlspecialchars($activity['course_name']); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo formatDate($activity['date']); ?> • 
                                <span class="<?php echo getStatusColor($activity['status']); ?>">
                                    <?php echo getStatusLabel($activity['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'completed':
            return 'text-green-600';
        case 'pending':
            return 'text-yellow-600';
        case 'cancelled':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
}
?>

<?php include '../../includes/footer.php'; ?>