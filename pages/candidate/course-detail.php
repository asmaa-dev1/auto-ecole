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

$enrollmentId = $_GET['id'] ?? 0;
$errors = [];

// Get enrollment details
$stmt = $conn->prepare("
    SELECT e.*, 
           c.name as course_name, 
           c.description as course_description,
           c.hours_theory,
           c.hours_practice,
           u.first_name as instructor_first_name,
           u.last_name as instructor_last_name,
           u.profile_image as instructor_image
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN users u ON e.instructor_id = u.id
    WHERE e.id = ? AND e.candidate_id = ?
");

$stmt->bind_param("ii", $enrollmentId, $_SESSION['user_id']);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();

if (!$enrollment) {
    header('Location: /auto-ecole/pages/candidate/courses.php');
    exit();
}

// Get sessions
$stmt = $conn->prepare("
    SELECT * FROM sessions 
    WHERE enrollment_id = ? 
    ORDER BY session_date ASC, start_time ASC
");
$stmt->bind_param("i", $enrollmentId);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate progress statistics
$totalSessions = count($sessions);
$completedSessions = array_reduce($sessions, function($carry, $session) {
    return $carry + ($session['status'] === 'completed' ? 1 : 0);
}, 0);

$progress = $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0;

// Get documents
$stmt = $conn->prepare("
    SELECT * FROM course_documents 
    WHERE course_id = (SELECT course_id FROM enrollments WHERE id = ?)
");
$stmt->bind_param("i", $enrollmentId);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get payments
$stmt = $conn->prepare("
    SELECT * FROM payments 
    WHERE enrollment_id = ? 
    ORDER BY payment_date DESC
");
$stmt->bind_param("i", $enrollmentId);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'active':
            return 'bg-blue-100 text-blue-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
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
        <!-- Course Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center mb-2">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($enrollment['course_name']); ?>
                        </h1>
                        <span class="ml-4 px-2.5 py-0.5 rounded-full text-sm font-medium 
                            <?php echo getStatusBadgeClass($enrollment['status']); ?>">
                            <?php echo getStatusLabel($enrollment['status']); ?>
                        </span>
                    </div>
                    <p class="text-gray-600">
                        <?php echo htmlspecialchars($enrollment['course_description']); ?>
                    </p>
                </div>
                <?php if ($enrollment['status'] === 'active'): ?>
                    <div class="text-right">
                        <div class="inline-flex items-center px-4 py-2 border border-primary rounded-md text-primary text-lg font-bold">
                            Progression: <?php echo round($progress); ?>%
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Progress Bar -->
            <?php if ($enrollment['status'] === 'active'): ?>
                <div class="mt-6">
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-primary rounded-full h-3" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <div class="mt-2 grid grid-cols-3 text-sm text-gray-600">
                        <div>
                            <span class="font-medium"><?php echo $completedSessions; ?></span> sessions complétées
                        </div>
                        <div class="text-center">
                            <span class="font-medium"><?php echo $totalSessions - $completedSessions; ?></span> sessions restantes
                        </div>
                        <div class="text-right">
                            <span class="font-medium"><?php echo $totalSessions; ?></span> sessions au total
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Sessions -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Upcoming Sessions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Sessions à venir</h2>
                    <?php 
                    $upcomingSessions = array_filter($sessions, function($session) {
                        return $session['session_date'] >= date('Y-m-d') && $session['status'] !== 'cancelled';
                    });
                    if (!empty($upcomingSessions)): 
                    ?>
                        <div class="space-y-4">
                            <?php foreach ($upcomingSessions as $session): ?>
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="w-16 h-16 bg-primary/10 rounded-lg flex items-center justify-center">
                                        <i class="fas <?php echo $session['session_type'] === 'theory' ? 'fa-book' : 'fa-car'; ?> text-2xl text-primary"></i>
                                    </div>
                                    <div class="ml-4 flex-grow">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?php echo $session['session_type'] === 'theory' ? 'Session Théorique' : 'Session Pratique'; ?>
                                            </h3>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getSessionStatusClass($session['status']); ?>">
                                                <?php echo getSessionStatusLabel($session['status']); ?>
                                            </span>
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                <?php echo formatDate($session['session_date']); ?>
                                            </div>
                                            <div class="flex items-center mt-1">
                                                <i class="fas fa-clock mr-2"></i>
                                                <?php echo substr($session['start_time'], 0, 5); ?> - 
                                                <?php echo substr($session['end_time'], 0, 5); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-center py-4">
                            Aucune session à venir programmée.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Course Materials -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Matériel de cours</h2>
                    <?php if (!empty($documents)): ?>
                        <div class="space-y-4">
                            <?php foreach ($documents as $document): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-primary/10 rounded flex items-center justify-center">
                                            <i class="fas <?php echo getDocumentIcon($document['type']); ?> text-primary"></i>
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($document['title']); ?>
                                            </h3>
                                            <p class="text-xs text-gray-500">
                                                <?php echo formatFileSize($document['file_size']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <a href="/download.php?id=<?php echo $document['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1 border border-primary rounded-md text-primary text-sm hover:bg-primary hover:text-white transition-colors">
                                        <i class="fas fa-download mr-2"></i>
                                        Télécharger
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-center py-4">
                            Aucun document disponible pour le moment.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Course Info -->
            <div class="space-y-6">
                <!-- Instructor Card -->
                <?php if ($enrollment['instructor_id']): ?>
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Instructeur</h2>
                        <div class="flex items-center">
                            <img src="<?php echo $enrollment['instructor_image'] 
                                ? '/assets/images/profiles/' . htmlspecialchars($enrollment['instructor_image'])
                                : 'https://ui-avatars.com/api/?name=' . urlencode($enrollment['instructor_first_name'] . ' ' . $enrollment['instructor_last_name']); ?>"
                                alt="Instructor" 
                                class="w-16 h-16 rounded-full object-cover">
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($enrollment['instructor_first_name'] . ' ' . $enrollment['instructor_last_name']); ?>
                                </h3>
                                <p class="text-sm text-gray-600">Instructeur professionnel</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Course Details Card -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Détails du cours</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Date de début</span>
                            <span class="font-medium"><?php echo formatDate($enrollment['start_date']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Heures de théorie</span>
                            <span class="font-medium"><?php echo $enrollment['hours_theory']; ?> heures</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Heures de pratique</span>
                            <span class="font-medium"><?php echo $enrollment['hours_practice']; ?> heures</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Sessions complétées</span>
                            <span class="font-medium"><?php echo $completedSessions; ?>/<?php echo $totalSessions; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payments Card -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Paiements</h2>
                    <?php if (!empty($payments)): ?>
                        <div class="space-y-4">
                            <?php foreach ($payments as $payment): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            <?php echo number_format($payment['amount'], 2); ?> DH
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo formatDate($payment['payment_date']); ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo getPaymentStatusClass($payment['status']); ?>">
                                        <?php echo getPaymentStatusLabel($payment['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-center py-4">
                            Aucun paiement enregistré.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getSessionStatusClass($status) {
    switch ($status) {
        case 'scheduled':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getSessionStatusLabel($status) {
    switch ($status) {
        case 'scheduled':
            return 'Programmé';
        case 'completed':
            return 'Terminé';
        case 'cancelled':
            return 'Annulé';
        default:
            return 'Non défini';
    }
}

function getDocumentIcon($type) {
    switch ($type) {
        case 'pdf':
            return 'fa-file-pdf';
        case 'doc':
        case 'docx':
            return 'fa-file-word';
        case 'xls':
        case 'xlsx':
            return 'fa-file-excel';
        case 'ppt':
        case 'pptx':
            return 'fa-file-powerpoint';
        case 'video':
            return 'fa-file-video';
        default:
            return 'fa-file';
    }
}

function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($size >= 1024 && $i < 3) {
        $size /= 1024;
        $i++;
    }
    return round($size, 1) . ' ' . $units[$i];
}

function getPaymentStatusClass($status) {
    switch ($status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getPaymentStatusLabel($status) {
    switch ($status) {
        case 'completed':
            return 'Payé';
        case 'pending':
            return 'En attente';
        case 'failed':
            return 'Échoué';
        default:
            return 'Non défini';
    }
}
?>
<?php include '../../includes/footer.php'; ?>