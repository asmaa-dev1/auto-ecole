<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
checkRole('assistant');

// Get filters from URL
$filter = $_GET['filter'] ?? 'today';
$type = $_GET['type'] ?? 'all';

// Get today's date
$today = date('Y-m-d');

// Build date range based on filter
switch ($filter) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    default: // today
        $startDate = $today;
        $endDate = $today;
}

// Get sessions
$query = "
    SELECT 
        s.*,
        c.name as course_name,
        c.id as course_id,
        u_candidate.first_name as candidate_first_name,
        u_candidate.last_name as candidate_last_name,
        u_instructor.first_name as instructor_first_name,
        u_instructor.last_name as instructor_last_name
    FROM sessions s
    JOIN enrollments e ON s.enrollment_id = e.id
    JOIN courses c ON e.course_id = c.id
    JOIN users u_candidate ON e.candidate_id = u_candidate.id
    JOIN users u_instructor ON e.instructor_id = u_instructor.id
    WHERE s.session_date BETWEEN ? AND ?
";

$params = [$startDate, $endDate];
$types = "ss";

if ($type !== 'all') {
    $query .= " AND s.session_type = ?";
    $params[] = $type;
    $types .= "s";
}

$query .= " ORDER BY s.session_date ASC, s.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle session status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $sessionId = $_POST['session_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';

    switch ($action) {
        case 'complete':
            $stmt = $conn->prepare("
                UPDATE sessions 
                SET status = 'completed', notes = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $notes, $sessionId);
            break;

        case 'cancel':
            $stmt = $conn->prepare("
                UPDATE sessions 
                SET status = 'cancelled', notes = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $notes, $sessionId);
            break;
    }

    if (isset($stmt) && $stmt->execute()) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=true');
        exit();
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <?php include '../../includes/assistant-sidebar.php'; ?>

    <div class="ml-64 p-8 pt-20">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gestion des Sessions</h1>
            <a href="/pages/assistant/schedule-session.php" 
               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                <i class="fas fa-calendar-plus mr-2"></i>
                Programmer une session
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
                            Session mise à jour avec succès
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="filter" class="block text-sm font-medium text-gray-700 mb-1">
                        Période
                    </label>
                    <select id="filter" name="filter" class="input-field">
                        <option value="today" <?php echo $filter === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                        <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                        <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                    </select>
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                        Type de session
                    </label>
                    <select id="type" name="type" class="input-field">
                        <option value="all">Tous les types</option>
                        <option value="theory" <?php echo $type === 'theory' ? 'selected' : ''; ?>>Théorie</option>
                        <option value="practice" <?php echo $type === 'practice' ? 'selected' : ''; ?>>Pratique</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-filter mr-2"></i>
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Sessions List -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <?php if (empty($sessions)): ?>
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <i class="fas fa-calendar text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        Aucune session trouvée
                    </h3>
                    <p class="text-gray-500">
                        Il n'y a pas de sessions programmées pour cette période.
                    </p>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Heure</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Candidat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instructeur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo formatDate($session['session_date']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo substr($session['start_time'], 0, 5); ?> - 
                                        <?php echo substr($session['end_time'], 0, 5); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $session['session_type'] === 'theory' 
                                            ? 'bg-blue-100 text-blue-800' 
                                            : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo $session['session_type'] === 'theory' ? 'Théorie' : 'Pratique'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($session['candidate_first_name'] . ' ' . $session['candidate_last_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($session['course_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($session['instructor_first_name'] . ' ' . $session['instructor_last_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo getSessionStatusClass($session['status']); ?>">
                                        <?php echo getSessionStatusLabel($session['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-3">
                                        <?php if ($session['status'] === 'scheduled'): ?>
                                            <button onclick="openSessionModal(<?php echo htmlspecialchars(json_encode($session)); ?>, 'complete')"
                                                    class="text-green-600 hover:text-green-900" 
                                                    title="Marquer comme terminée">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            
                                            <button onclick="openSessionModal(<?php echo htmlspecialchars(json_encode($session)); ?>, 'cancel')"
                                                    class="text-red-600 hover:text-red-900" 
                                                    title="Annuler">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>

                                        <a href="/pages/assistant/session-detail.php?id=<?php echo $session['id']; ?>" 
                                           class="text-primary hover:text-accent" 
                                           title="Voir les détails">
                                            <i class="fas fa-eye"></i>
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

<!-- Session Update Modal -->
<div id="sessionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Mettre à jour la session</h3>
            <form id="sessionUpdateForm" method="POST">
                <input type="hidden" name="session_id" id="sessionId">
                <input type="hidden" name="action" id="sessionAction">

                <div class="mb-4">
                    <label for="sessionNotes" class="block text-sm font-medium text-gray-700 mb-1">
                        Notes (optionnel)
                    </label>
                    <textarea id="sessionNotes" name="notes" rows="3" 
                              class="input-field"
                              placeholder="Ajoutez des notes sur la session..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeSessionModal()"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        Annuler
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent">
                        Confirmer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-submit form when filters change
document.querySelectorAll('select[name="filter"], select[name="type"]').forEach(select => {
    select.addEventListener('change', () => {
        select.closest('form').submit();
    });
});

// Session modal handling
function openSessionModal(session, action) {
    const modal = document.getElementById('sessionModal');
    const modalTitle = document.getElementById('modalTitle');
    const sessionId = document.getElementById('sessionId');
    const sessionAction = document.getElementById('sessionAction');
    
    modalTitle.textContent = action === 'complete' 
        ? 'Marquer la session comme terminée' 
        : 'Annuler la session';
    
    sessionId.value = session.id;
    sessionAction.value = action;
    
    modal.classList.remove('hidden');
}

function closeSessionModal() {
    document.getElementById('sessionModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('sessionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSessionModal();
    }
});

// Helper functions
function getSessionStatusClass(status) {
    switch (status) {
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

function getSessionStatusLabel(status) {
    switch (status) {
        case 'scheduled':
            return 'Programmée';
        case 'completed':
            return 'Terminée';
        case 'cancelled':
            return 'Annulée';
        default:
            return status;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>