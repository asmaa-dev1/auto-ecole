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

// Get all assistants
$stmt = $conn->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT a.id) as total_actions
    FROM users u 
    LEFT JOIN activity_logs a ON u.id = a.user_id
    WHERE u.role = 'assistant'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$assistants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $assistantId = $_POST['assistant_id'] ?? 0;
    $action = $_POST['action'];
    
    $conn->begin_transaction();
    try {
        switch ($action) {
            case 'approve':
                $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'assistant'");
                $stmt->bind_param("i", $assistantId);
                $stmt->execute();
                break;
                
            case 'suspend':
                $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = ? AND role = 'assistant'");
                $stmt->bind_param("i", $assistantId);
                $stmt->execute();
                break;
                
            case 'delete':
                // First delete related records
                $stmt = $conn->prepare("DELETE FROM activity_logs WHERE user_id = ?");
                $stmt->bind_param("i", $assistantId);
                $stmt->execute();
                
                // Then delete the user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'assistant'");
                $stmt->bind_param("i", $assistantId);
                $stmt->execute();
                break;
        }
        
        $conn->commit();
        $success = true;
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=true');
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $errors['general'] = 'Une erreur est survenue: ' . $e->getMessage();
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
            <h1 class="text-2xl font-bold text-gray-900">Gestion des Assistants</h1>
            <a href="/pages/admin/add-assistant.php" 
               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Ajouter un assistant
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
                        <p class="text-sm text-green-700">
                            L'opération a été effectuée avec succès.
                        </p>
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
                           placeholder="Nom, email...">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select id="status" name="status" class="input-field">
                        <option value="">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="pending">En attente</option>
                        <option value="suspended">Suspendu</option>
                    </select>
                </div>

                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                    <select id="sort" name="sort" class="input-field">
                        <option value="created_desc">Date (Plus récent)</option>
                        <option value="created_asc">Date (Plus ancien)</option>
                        <option value="name_asc">Nom (A-Z)</option>
                        <option value="name_desc">Nom (Z-A)</option>
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

        <!-- Assistants List -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Assistant
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date d'inscription
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions effectuées
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
                        <?php foreach ($assistants as $assistant): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full" 
                                                 src="<?php echo $assistant['profile_image'] 
                                                    ? '/assets/images/profiles/' . htmlspecialchars($assistant['profile_image'])
                                                    : 'https://ui-avatars.com/api/?name=' . urlencode($assistant['first_name'] . ' ' . $assistant['last_name']); ?>" 
                                                 alt="Profile">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($assistant['first_name'] . ' ' . $assistant['last_name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($assistant['email']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($assistant['phone']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($assistant['created_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $assistant['total_actions']; ?> actions
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo getStatusBadgeClass($assistant['status']); ?>">
                                        <?php echo getStatusLabel($assistant['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <?php if ($assistant['status'] === 'pending'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="assistant_id" value="<?php echo $assistant['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" 
                                                        class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($assistant['status'] === 'active'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="assistant_id" value="<?php echo $assistant['id']; ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" 
                                                        class="text-orange-600 hover:text-orange-900">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet assistant ?');">
                                            <input type="hidden" name="assistant_id" value="<?php echo $assistant['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                        <a href="/pages/admin/edit-assistant.php?id=<?php echo $assistant['id']; ?>" 
                                           class="text-primary hover:text-accent">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-submit form when filters change
document.querySelectorAll('select[name="status"], select[name="sort"]').forEach(select => {
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