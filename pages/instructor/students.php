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

// Get all active students
$stmt = $conn->prepare("
    SELECT 
        u.id as student_id,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        e.id as enrollment_id,
        e.start_date,
        e.status as enrollment_status,
        c.name as course_name,
        c.id as course_id,
        COUNT(DISTINCT s.id) as total_sessions,
        SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions
    FROM enrollments e
    JOIN users u ON e.candidate_id = u.id
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN sessions s ON e.id = s.enrollment_id
    WHERE e.instructor_id = ?
    GROUP BY e.id
    ORDER BY e.start_date DESC
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Filter options
$courseFilter = $_GET['course'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Get available courses for filter
$stmt = $conn->prepare("
    SELECT DISTINCT c.id, c.name
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.instructor_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Apply filters
$filteredStudents = array_filter($students, function($student) use ($courseFilter, $statusFilter, $searchQuery) {
    $matchesCourse = $courseFilter === 'all' || $student['course_id'] == $courseFilter;
    $matchesStatus = $statusFilter === 'all' || $student['enrollment_status'] === $statusFilter;
    $matchesSearch = empty($searchQuery) || 
        stripos($student['first_name'] . ' ' . $student['last_name'], $searchQuery) !== false ||
        stripos($student['email'], $searchQuery) !== false;
    
    return $matchesCourse && $matchesStatus && $matchesSearch;
});
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/instructor-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Mes Étudiants</h1>
                <a href="/auto-ecole/pages/instructor/schedule-session.php" 
                   class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Programmer une session
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Course Filter -->
                    <div>
                        <label for="course" class="block text-sm font-medium text-gray-700 mb-1">Cours</label>
                        <select name="course" id="course" class="input-field">
                            <option value="all">Tous les cours</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" 
                                    <?php echo $courseFilter == $course['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <select name="status" id="status" class="input-field">
                            <option value="all">Tous les statuts</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Actif</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                        <input type="text" name="search" id="search" 
                               class="input-field"
                               placeholder="Nom, email..."
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>

                    <!-- Submit -->
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-filter mr-2"></i>
                            Filtrer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Students List -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <?php if (!empty($filteredStudents)): ?>
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
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($filteredStudents as $student): ?>
                                <?php 
                                    $progressPercentage = $student['total_sessions'] > 0 
                                        ? ($student['completed_sessions'] / $student['total_sessions']) * 100 
                                        : 0;
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($student['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($student['course_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo formatDate($student['start_date']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-primary rounded-full h-2.5" 
                                                 style="width: <?php echo $progressPercentage; ?>%">
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">
                                            <?php echo $student['completed_sessions']; ?>/<?php echo $student['total_sessions']; ?> sessions
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo getStatusBadgeClass($student['enrollment_status']); ?>">
                                            <?php echo getStatusLabel($student['enrollment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-3">
                                            <a href="/auto-ecole/pages/instructor/student-detail.php?enrollment_id=<?php echo $student['enrollment_id']; ?>" 
                                               class="text-primary hover:text-accent">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/auto-ecole/pages/instructor/schedule-session.php?student_id=<?php echo $student['student_id']; ?>" 
                                               class="text-green-600 hover:text-green-700">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                            <a href="/auto-ecole/pages/instructor/progress-report.php?enrollment_id=<?php echo $student['enrollment_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-700">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="mb-4">
                            <i class="fas fa-users text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Aucun étudiant trouvé
                        </h3>
                        <p class="text-gray-600">
                            Essayez d'ajuster vos filtres ou de faire une nouvelle recherche.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-submit form when filters change
document.querySelectorAll('select[name="course"], select[name="status"]').forEach(select => {
    select.addEventListener('change', () => {
        select.closest('form').submit();
    });
});

// Debounce search input
let timeout = null;
document.querySelector('input[name="search"]').addEventListener('input', function(e) {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        this.closest('form').submit();
    }, 500);
});
</script>

<?php include '../../includes/footer.php'; ?>