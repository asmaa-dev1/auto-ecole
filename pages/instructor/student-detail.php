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

$enrollmentId = $_GET['enrollment_id'] ?? 0;

// Get enrollment details with student and course info
$stmt = $conn->prepare("
    SELECT 
        e.*,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        u.profile_image,
        c.name as course_name,
        c.description as course_description,
        c.hours_theory,
        c.hours_practice,
        lt.name as license_type,
        lt.code as license_code
    FROM enrollments e
    JOIN users u ON e.candidate_id = u.id
    JOIN courses c ON e.course_id = c.id
    JOIN license_types lt ON c.license_type_id = lt.id
    WHERE e.id = ? AND e.instructor_id = ?
");

$stmt->bind_param("ii", $enrollmentId, $_SESSION['user_id']);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();

if (!$enrollment) {
    header('Location: /auto-ecole/pages/instructor/students.php');
    exit();
}

// Get all sessions
$stmt = $conn->prepare("
    SELECT * FROM sessions 
    WHERE enrollment_id = ? 
    ORDER BY session_date DESC, start_time DESC
");
$stmt->bind_param("i", $enrollmentId);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate progress statistics
$totalSessions = count($sessions);
$completedSessions = array_reduce($sessions, function($carry, $session) {
    return $carry + ($session['status'] === 'completed' ? 1 : 0);
}, 0);

$theorySessions = array_filter($sessions, function($session) {
    return $session['session_type'] === 'theory';
});
$practiceSessions = array_filter($sessions, function($session) {
    return $session['session_type'] === 'practice';
});

$progress = [
    'total' => $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0,
    'theory' => count($theorySessions) > 0 ? 
        (count(array_filter($theorySessions, fn($s) => $s['status'] === 'completed')) / count($theorySessions) * 100) : 0,
    'practice' => count($practiceSessions) > 0 ?
    (count(array_filter($practiceSessions, fn($s) => $s['status'] === 'completed')) / count($practiceSessions) * 100) : 0,
];


// Handle session status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_session'])) {
    $sessionId = $_POST['session_id'];
    $newStatus = $_POST['status'];
    $notes = sanitizeInput($_POST['session_notes']);

    $stmt = $conn->prepare("
        UPDATE sessions 
        SET status = ?, notes = ? 
        WHERE id = ? AND enrollment_id = ?
    ");
    $stmt->bind_param("ssii", $newStatus, $notes, $sessionId, $enrollmentId);
    
    if ($stmt->execute()) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?enrollment_id=' . $enrollmentId . '&success=session_updated');
        exit();
    }
}

?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/instructor-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <!-- Success Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            <?php
                            switch ($_GET['success']) {
                                case 'session_updated':
                                    echo 'La session a été mise à jour avec succès.';
                                    break;
                                case 'note_added':
                                    echo 'La note a été ajoutée avec succès.';
                                    break;
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Student Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    <img src="<?php echo $enrollment['profile_image'] 
                        ? '/assets/images/profiles/' . htmlspecialchars($enrollment['profile_image'])
                        : 'https://ui-avatars.com/api/?name=' . urlencode($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?>"
                        alt="Profile" 
                        class="w-16 h-16 rounded-full object-cover">
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?>
                        </h1>
                        <div class="mt-1 text-sm text-gray-600">
                            <p>
                                <i class="fas fa-envelope mr-2"></i>
                                <?php echo htmlspecialchars($enrollment['email']); ?>
                            </p>
                            <p>
                                <i class="fas fa-phone mr-2"></i>
                                <?php echo htmlspecialchars($enrollment['phone']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 text-sm font-medium rounded-full 
                        <?php echo getStatusBadgeClass($enrollment['status']); ?>">
                        <?php echo getStatusLabel($enrollment['status']); ?>
                    </span>
                    <div class="mt-2">
                        <span class="text-sm font-medium text-gray-600">
                            Début: <?php echo formatDate($enrollment['start_date']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Progress -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Overall Progress -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Progression Globale</h3>
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Total</span>
                        <span><?php echo round($progress['total']); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-primary rounded-full h-2.5" 
                             style="width: <?php echo $progress['total']; ?>%">
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    <p><?php echo $completedSessions; ?> sessions complétées sur <?php echo $totalSessions; ?></p>
                </div>
            </div>

            <!-- Theory Progress -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Formation Théorique</h3>
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Théorie</span>
                        <span><?php echo round($progress['theory']); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-500 rounded-full h-2.5" 
                             style="width: <?php echo $progress['theory']; ?>%">
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    <p><?php echo count($theorySessions); ?> sessions théoriques programmées</p>
                </div>
            </div>

            <!-- Practical Progress -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Formation Pratique</h3>
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Pratique</span>
                        <span><?php echo round($progress['practice']); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-green-500 rounded-full h-2.5" 
                             style="width: <?php echo $progress['practice']; ?>%">
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    <p><?php echo count($practiceSessions); ?> sessions pratiques programmées</p>
                </div>
            </div>
        </div>

        <!-- Sessions and Notes Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sessions List -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Sessions</h3>
                        <a href="/auto-ecole/pages/instructor/schedule-session.php?student_id=<?php echo $enrollment['candidate_id']; ?>" 
                           class="px-3 py-1 bg-primary text-white rounded-lg text-sm hover:bg-accent transition-colors">
                            <i class="fas fa-plus mr-1"></i>
                            Nouvelle session
                        </a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo formatDate($session['session_date']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo substr($session['start_time'], 0, 5); ?> - 
                                            <?php echo substr($session['end_time'], 0, 5); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $session['session_type'] === 'theory' 
                                                ? 'bg-blue-100 text-blue-800' 
                                                : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo $session['session_type'] === 'theory' ? 'Théorie' : 'Pratique'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo getSessionStatusClass($session['status']); ?>">
                                            <?php echo getSessionStatusLabel($session['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="openSessionModal(<?php echo htmlspecialchars(json_encode($session)); ?>)"
                                                class="text-primary hover:text-accent">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="space-y-6">
                <!-- Add Note Form -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ajouter une note</h3>
                    <form action="" method="POST">
                        <div class="mb-4">
                            <textarea name="note_content" rows="4" 
                                    class="input-field"
                                    placeholder="Entrez votre note..."></textarea>
                        </div>
                        <button type="submit" name="add_note"
                                class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                            Ajouter la note
                        </button>
                    </form>
                </div>

                <!-- Recent Notes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes récentes</h3>
                    <?php if (!empty($notes)): ?>
                        <div class="space-y-4">
                            <?php foreach ($notes as $note): ?>
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-600 mb-2">
                                        <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo formatDate($note['created_at']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-center">
                            Aucune note pour le moment.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Session Update Modal -->
<div id="sessionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Mettre à jour la session</h3>
                    <button onclick="closeSessionModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="sessionUpdateForm" method="POST" class="space-y-4">
                    <input type="hidden" name="update_session" value="1">
                    <input type="hidden" name="session_id" id="sessionId">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Statut
                        </label>
                        <select name="status" id="sessionStatus" class="input-field">
                            <option value="scheduled">Programmé</option>
                            <option value="completed">Terminé</option>
                            <option value="cancelled">Annulé</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Notes
                        </label>
                        <textarea name="session_notes" id="sessionNotes" rows="4" 
                                class="input-field"
                                placeholder="Entrez vos notes sur la session..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeSessionModal()"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Annuler
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-primary text-white rounded-md hover:bg-accent">
                            Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openSessionModal(session) {
    document.getElementById('sessionId').value = session.id;
    document.getElementById('sessionStatus').value = session.status;
    document.getElementById('sessionNotes').value = session.notes || '';
    document.getElementById('sessionModal').classList.remove('hidden');
}

function closeSessionModal() {
    document.getElementById('sessionModal').classList.add('hidden');
}
</script>

<?php include '../../includes/footer.php'; ?>