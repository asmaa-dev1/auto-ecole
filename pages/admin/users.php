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

// Ensure user is logged in and is an admin
$auth->requireLogin();
$auth->checkRole('admin');

// Get all instructors
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        u.profile_image,
        u.created_at,
        u.status,
        COUNT(DISTINCT e.id) as total_students,
        COUNT(DISTINCT s.id) as total_sessions
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.instructor_id
    LEFT JOIN sessions s ON e.id = s.enrollment_id
    WHERE u.role = 'instructor'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$instructors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $instructorId = $_POST['instructor_id'];
    $newStatus = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'instructor'");
    $stmt->bind_param("si", $newStatus, $instructorId);
    
    if ($stmt->execute()) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=status_updated');
        exit();
    }
}

?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/admin-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Gestion des Instructeurs</h1>
            <a href="/auto-ecole/pages/admin/add-instructor.php" 
               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Ajouter un instructeur
            </a>
        </div>

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
                                case 'status_updated':
                                    echo 'Le statut de l\'instructeur a été mis à jour avec succès.';
                                    break;
                                case 'instructor_added':
                                    echo 'L\'instructeur a été ajouté avec succès.';
                                    break;
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Instructors Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($instructors as $instructor): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Header -->
                    <div class="p-6">
                        <div class="flex items-center">
                            <img src="<?php echo $instructor['profile_image'] 
                                ? '/auto-ecole/assets/images/profiles/' . htmlspecialchars($instructor['profile_image'])
                                : 'https://ui-avatars.com/api/?name=' . urlencode($instructor['first_name'] . ' ' . $instructor['last_name']); ?>"
                                alt="Profile" 
                                class="w-16 h-16 rounded-full object-cover">
                            <div class="ml-4">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                                </h2>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($instructor['email']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="border-t border-gray-200 px-6 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-600">Étudiants</p>
                                <p class="mt-1 text-xl font-semibold text-primary">
                                    <?php echo $instructor['total_students']; ?>
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-600">Sessions</p>
                                <p class="mt-1 text-xl font-semibold text-primary">
                                    <?php echo $instructor['total_sessions']; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status & Actions -->
                    <div class="border-t border-gray-200 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php echo getStatusBadgeClass($instructor['status']); ?>">
                                    <?php echo getStatusLabel($instructor['status']); ?>
                                </span>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="openStatusModal(<?php echo htmlspecialchars(json_encode($instructor)); ?>)"
                                        class="p-1 text-gray-600 hover:text-primary">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="/auto-ecole/pages/admin/instructor-detail.php?id=<?php echo $instructor['id']; ?>"
                                   class="p-1 text-gray-600 hover:text-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <form id="statusUpdateForm" method="POST" class="p-6">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="instructor_id" id="instructorId">
                
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Modifier le statut</h3>
                    <button type="button" onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Statut
                    </label>
                    <select name="status" id="instructorStatus" class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                        <option value="active">Actif</option>
                        <option value="inactive" selected>Inactif</option>
                     <option value="suspended">Suspendu</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeStatusModal()"
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

<script>
function openStatusModal(instructor) {
    document.getElementById('instructorId').value = instructor.id;
    document.getElementById('instructorStatus').value = instructor.status;
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}
</script>

<?php include '../../includes/footer.php'; ?>