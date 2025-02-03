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
$auth->checkRole('admin');

$success = false;
$errors = [];

// Get all license types for filter
$stmt = $conn->prepare("
    SELECT id, name, code, description 
    FROM license_types 
    WHERE duration IS NOT NULL 
    ORDER BY name ASC
");
$stmt->execute();
$licenseTypes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build query conditions based on filters
$conditions = ["c.status != 'deleted'"]; // Base condition
$params = [];
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $conditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (isset($_GET['license_type']) && !empty($_GET['license_type'])) {
    $conditions[] = "lt.code = ?";
    $params[] = $_GET['license_type'];
    $types .= "s";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $conditions[] = "c.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Get all courses with related data
$query = "
    SELECT 
        c.*,
        lt.name as license_type_name,
        lt.code as license_type_code,
        lt.description as license_type_description,
        COUNT(DISTINCT e.id) as total_enrollments
    FROM courses c
    LEFT JOIN license_types lt ON c.license_type_id = lt.id
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
    WHERE " . implode(' AND ', $conditions) . "
    GROUP BY c.id
    ORDER BY c.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle course status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $courseId = $_POST['course_id'];
    $newStatus = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE courses SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $courseId);
    
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gestion des Cours</h1>
            <a href="/auto-ecole/pages/admin/add-course.php" 
               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Ajouter un cours
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">L'opération a été effectuée avec succès.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                    <input type="text" id="search" name="search" 
                           class="input-field"
                           placeholder="Nom du cours..."
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>

                <div>
                    <label for="license_type" class="block text-sm font-medium text-gray-700 mb-1">Type de permis</label>
                    <select id="license_type" name="license_type" class="input-field">
                        <option value="">Tous les types</option>
                        <?php foreach ($licenseTypes as $type): ?>
                            <option value="<?php echo $type['code']; ?>"
                                <?php echo (isset($_GET['license_type']) && $_GET['license_type'] === $type['code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select id="status" name="status" class="input-field">
                        <option value="">Tous les statuts</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>Actif</option>
                        <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : ''; ?>>Inactif</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-filter mr-2"></i>
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Courses Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($courses as $course): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Course Header -->
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($course['name']); ?>
                            </h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?php echo htmlspecialchars($course['license_type_code']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">
                            <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                        </p>
                    </div>

                    <!-- Course Stats -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-xs text-gray-500">Prix</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    <?php echo number_format($course['course_price'], 2); ?> DH
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Heures Théorie</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    <?php echo $course['hours_theory']; ?>h
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Heures Pratique</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    <?php echo $course['hours_practice']; ?>h
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Course Actions -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                <?php echo $course['status'] === 'active' 
                                    ? 'bg-green-100 text-green-800' 
                                    : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $course['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                            </span>
                            <div class="flex space-x-2">
                                <a href="/auto-ecole/pages/admin/edit-course.php?id=<?php echo $course['id']; ?>"
                                   class="p-2 text-primary hover:text-accent transition-colors">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="toggleCourseStatus(<?php echo $course['id']; ?>, '<?php echo $course['status'] === 'active' ? 'inactive' : 'active'; ?>')"
                                        class="p-2 text-gray-600 hover:text-primary transition-colors">
                                    <i class="fas <?php echo $course['status'] === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                </button>
                                <a href="/auto-ecole/pages/admin/course-detail.php?id=<?php echo $course['id']; ?>"
                                   class="p-2 text-primary hover:text-accent transition-colors">
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

<!-- Status Update Form -->
<form id="statusUpdateForm" method="POST" class="hidden">
    <input type="hidden" name="update_status" value="1">
    <input type="hidden" name="course_id" id="courseId">
    <input type="hidden" name="status" id="courseStatus">
</form>

<script>
// Auto-submit form when filters change
document.querySelectorAll('select[name="license_type"], select[name="status"]').forEach(select => {
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

function toggleCourseStatus(courseId, newStatus) {
    if (confirm('Êtes-vous sûr de vouloir modifier le statut de ce cours ?')) {
        document.getElementById('courseId').value = courseId;
        document.getElementById('courseStatus').value = newStatus;
        document.getElementById('statusUpdateForm').submit();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>