<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
checkRole('assistant');

// Get filters from URL
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$course = $_GET['course'] ?? 'all';

// Get available courses for filter
$stmt = $conn->prepare("SELECT id, name FROM courses WHERE status = 'active'");
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build query for candidates
$query = "
    SELECT 
        u.*,
        e.id as enrollment_id,
        e.status as enrollment_status,
        e.start_date,
        c.name as course_name,
        i.first_name as instructor_first_name,
        i.last_name as instructor_last_name
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.candidate_id
    LEFT JOIN courses c ON e.course_id = c.id
    LEFT JOIN users i ON e.instructor_id = i.id
    WHERE u.role = 'candidate'
";

$params = [];
$types = "";

if ($status !== 'all') {
    $query .= " AND u.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($course !== 'all') {
    $query .= " AND (c.id = ? OR c.id IS NULL)";
    $params[] = $course;
    $types .= "i";
}

if (!empty($search)) {
    $query .= " AND (
        u.first_name LIKE ? OR 
        u.last_name LIKE ? OR 
        u.email LIKE ? OR
        u.phone LIKE ?
    )";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssss";
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateId = $_POST['candidate_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if ($candidateId && $action) {
        switch ($action) {
            case 'approve':
                $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                break;
            case 'reject':
                $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
                break;
            case 'suspend':
                $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                break;
        }

        if (isset($stmt)) {
            $stmt->bind_param("i", $candidateId);
            if ($stmt->execute()) {
                // Log the action
                logAction("A modifié le statut du candidat #$candidateId en '$action'");
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=true");
                exit();
            }
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <?php include '../../includes/assistant-sidebar.php'; ?>

    <div class="ml-64 p-8 pt-20">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gestion des Candidats</h1>
            <a href="/pages/assistant/add-candidate.php" 
               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                <i class="fas fa-user-plus mr-2"></i>
                Ajouter un candidat
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
                            L'opération a été effectuée avec succès
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                        Rechercher
                    </label>
                    <input type="text" id="search" name="search" 
                           class="input-field"
                           placeholder="Nom, email, téléphone..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Statut
                    </label>
                    <select id="status" name="status" class="input-field">
                        <option value="all">Tous les statuts</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Actif</option>
                        <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspendu</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                    </select>
                </div>

                <!-- Course Filter -->
                <div>
                    <label for="course" class="block text-sm font-medium text-gray-700 mb-1">
                        Formation
                    </label>
                    <select id="course" name="course" class="input-field">
                        <option value="all">Toutes les formations</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" 
                                <?php echo $course == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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

        <!-- Candidates List -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <?php if (empty($candidates)): ?>
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <i class="fas fa-users text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        Aucun candidat trouvé
                    </h3>
                    <p class="text-gray-500">
                        Essayez de modifier vos filtres ou d'ajouter un nouveau candidat.
                    </p>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Candidat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Formation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instructeur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($candidates as $candidate): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full" 
                                                src="<?php echo $candidate['profile_image'] 
                                                    ? '/assets/images/profiles/' . htmlspecialchars($candidate['profile_image'])
                                                    : 'https://ui-avatars.com/api/?name=' . urlencode($candidate['first_name'] . ' ' . $candidate['last_name']); ?>" 
                                                alt="Profile">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                Inscrit le <?php echo formatDate($candidate['created_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($candidate['email']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($candidate['phone']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($candidate['course_name']): ?>
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($candidate['course_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Début: <?php echo formatDate($candidate['start_date']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-500">Non inscrit</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($candidate['instructor_first_name']): ?>
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($candidate['instructor_first_name'] . ' ' . $candidate['instructor_last_name']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-500">Non assigné</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo getStatusBadgeClass($candidate['status']); ?>">
                                        <?php echo getStatusLabel($candidate['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-3">
                                        <a href="/pages/assistant/candidate-detail.php?id=<?php echo $candidate['id']; ?>" 
                                           class="text-primary hover:text-accent" 
                                           title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <?php if ($candidate['status'] === 'pending'): ?>
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" 
                                                        class="text-green-600 hover:text-green-900" 
                                                        title="Approuver">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>

                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-900" 
                                                        title="Rejeter">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($candidate['status'] === 'active'): ?>
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" 
                                                        class="text-orange-600 hover:text-orange-900" 
                                                        title="Suspendre">
                                                        <i class="fas fa-ban"></i>
                                                        </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="/pages/assistant/edit-candidate.php?id=<?php echo $candidate['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900" 
                                           title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4" id="modalTitle">Confirmation</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="modalMessage">
                    Êtes-vous sûr de vouloir effectuer cette action ?
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmButton"
                    class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Confirmer
                </button>
                <button id="cancelButton"
                    class="mt-3 px-4 py-2 bg-white text-gray-700 text-base font-medium rounded-md w-full border border-gray-300 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-submit form when filters change
document.querySelectorAll('select[name="status"], select[name="course"]').forEach(select => {
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

// Confirmation modal handling
function showConfirmationModal(title, message, onConfirm) {
    const modal = document.getElementById('confirmationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmButton = document.getElementById('confirmButton');
    const cancelButton = document.getElementById('cancelButton');

    modalTitle.textContent = title;
    modalMessage.textContent = message;

    modal.classList.remove('hidden');

    confirmButton.onclick = () => {
        onConfirm();
        modal.classList.add('hidden');
    };

    cancelButton.onclick = () => {
        modal.classList.add('hidden');
    };

    // Close modal when clicking outside
    modal.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    };
}

// Add confirmation for status change actions
document.querySelectorAll('form').forEach(form => {
    if (form.elements['action']) {
        form.onsubmit = (e) => {
            e.preventDefault();
            const action = form.elements['action'].value;
            const candidateId = form.elements['candidate_id'].value;

            let title = 'Confirmation';
            let message = 'Êtes-vous sûr de vouloir effectuer cette action ?';

            switch (action) {
                case 'approve':
                    title = 'Approuver le candidat';
                    message = 'Voulez-vous vraiment approuver ce candidat ?';
                    break;
                case 'reject':
                    title = 'Rejeter le candidat';
                    message = 'Voulez-vous vraiment rejeter ce candidat ?';
                    break;
                case 'suspend':
                    title = 'Suspendre le candidat';
                    message = 'Voulez-vous vraiment suspendre ce candidat ?';
                    break;
            }

            showConfirmationModal(title, message, () => {
                form.submit();
            });
        };
    }
});
</script>

<?php include '../../includes/footer.php'; ?>