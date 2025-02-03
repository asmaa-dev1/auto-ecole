<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Initialize Auth
$auth = Auth::getInstance();
$auth->requireLogin();
$auth->checkRole('candidate');

// Get user's enrollments
$enrollments = getEnrollmentsByUser($_SESSION['user_id']);

// Get available courses
$availableCourses = [];
$stmt = $conn->prepare("
    SELECT c.*, lt.name as license_type_name, lt.code as license_type_code
    FROM courses c
    JOIN license_types lt ON c.license_type_id = lt.id
    WHERE c.status = 'active'
    AND c.id NOT IN (
        SELECT course_id 
        FROM enrollments 
        WHERE candidate_id = ?
    )
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$availableCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle enrollment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $courseId = sanitizeInput($_POST['course_id']);
    
    // Verify course exists and user isn't already enrolled
    $stmt = $conn->prepare("
        SELECT c.* FROM courses c
        WHERE c.id = ?
        AND c.id NOT IN (
            SELECT course_id FROM enrollments WHERE candidate_id = ?
        )
    ");
    $stmt->bind_param("ii", $courseId, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 1) {
        // Create enrollment
        $stmt = $conn->prepare("
            INSERT INTO enrollments (candidate_id, course_id, status, start_date)
            VALUES (?, ?, 'pending', CURDATE())
        ");
        $stmt->bind_param("ii", $_SESSION['user_id'], $courseId);
        
        if ($stmt->execute()) {
            header('Location: /auto-ecole/pages/candidate/courses.php?enrolled=success');
            exit();
        }
    }
}

function getStatusLabel($status) {
    switch ($status) {
        case 'completed':
            return 'Terminé';
        case 'pending':
            return 'En attente';
        case 'active':
            return 'En cours';
        case 'cancelled':
            return 'Annulé';
        default:
            return 'Non défini';
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'completed':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

?>
<?php include '../../includes/header.php'; ?>
<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/candidate-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <!-- Enrolled Courses -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Mes Cours</h2>
            
            <?php if (isset($_GET['enrolled']) && $_GET['enrolled'] === 'success'): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                Inscription au cours réussie! Notre équipe examinera votre demande.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($enrollments)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($enrollments as $enrollment): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($enrollment['course_name']); ?>
                                    </h3>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo getStatusBadgeClass($enrollment['status']); ?>">
                                        <?php echo getStatusLabel($enrollment['status']); ?>
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-user-tie mr-2"></i>
                                        Instructeur: <?php echo htmlspecialchars($enrollment['instructor_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-calendar mr-2"></i>
                                        Début: <?php echo formatDate($enrollment['start_date']); ?>
                                    </p>
                                    
                                    <?php if ($enrollment['status'] === 'active'): ?>
                                        <div class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                <span>Progression</span>
                                                <span><?php echo calculateProgress($enrollment['id']); ?>%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-primary rounded-full h-2"
                                                    style="width: <?php echo calculateProgress($enrollment['id']); ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-6">
                                    <a href="/auto-ecole/pages/candidate/course-detail.php?id=<?php echo $enrollment['id']; ?>"
                                        class="inline-flex items-center px-4 py-2 border border-primary text-sm font-medium rounded-md text-primary hover:bg-primary hover:text-white transition-colors">
                                        Voir les détails
                                        <i class="fas fa-arrow-right ml-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <div class="mb-4">
                        <i class="fas fa-book text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        Aucun cours inscrit
                    </h3>
                    <p class="text-gray-600 mb-4">
                        Inscrivez-vous à un cours pour commencer votre formation.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Courses -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Cours Disponibles</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($availableCourses as $course): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($course['name']); ?>
                                    </h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($course['license_type_code']); ?>
                                    </span>
                                </div>
                                <span class="text-lg font-bold text-primary">
                                    <?php echo number_format($course['price'], 2); ?> DH
                                </span>
                            </div>

                            <div class="space-y-2 mb-4">
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($course['description']); ?>
                                </p>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span>
                                        <?php echo $course['hours_theory']; ?> heures de théorie
                                    </span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-car mr-2"></i>
                                    <span>
                                        <?php echo $course['hours_practice']; ?> heures de pratique
                                    </span>
                                </div>
                            </div>

                            <form action="" method="POST" class="mt-6">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" name="enroll" 
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                                    S'inscrire au cours
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>