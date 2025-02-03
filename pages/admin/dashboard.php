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

// Get statistics
$stats = [
    'total_students' => 0,
    'active_students' => 0,
    'total_instructors' => 0,
    'total_courses' => 0,
    'pending_approvals' => 0,
    'monthly_revenue' => 0,
];

// Get total and active students
$result = $conn->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_students,
        SUM(CASE WHEN e.status = 'active' THEN 1 ELSE 0 END) as active_students
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.candidate_id
    WHERE u.role = 'candidate'
");
$studentStats = $result->fetch_assoc();
$stats['total_students'] = $studentStats['total_students'];
$stats['active_students'] = $studentStats['active_students'];

// Get total instructors
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'instructor'");
$stats['total_instructors'] = $result->fetch_assoc()['count'];

// Get total courses
$result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
$stats['total_courses'] = $result->fetch_assoc()['count'];

// Get pending approvals
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'");
$stats['pending_approvals'] = $result->fetch_assoc()['count'];

// Get monthly revenue
$result = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM payments
    WHERE status = 'completed'
    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
    AND YEAR(payment_date) = YEAR(CURRENT_DATE())
");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'];

// Get recent enrollments
$recentEnrollments = [];
$result = $conn->query("
    SELECT 
        e.*,
        c.name as course_name,
        u_student.first_name as student_first_name,
        u_student.last_name as student_last_name,
        u_instructor.first_name as instructor_first_name,
        u_instructor.last_name as instructor_last_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u_student ON e.candidate_id = u_student.id
    LEFT JOIN users u_instructor ON e.instructor_id = u_instructor.id
    ORDER BY e.created_at DESC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recentEnrollments[] = $row;
}

// Get pending users
$pendingUsers = [];
$result = $conn->query("
    SELECT * FROM users 
    WHERE status = 'pending' 
    ORDER BY created_at DESC 
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $pendingUsers[] = $row;
}

// Get recent payments
$recentPayments = [];
$result = $conn->query("
    SELECT 
        p.*,
        u.first_name,
        u.last_name,
        c.name as course_name
    FROM payments p
    JOIN enrollments e ON p.enrollment_id = e.id
    JOIN users u ON e.candidate_id = u.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY p.payment_date DESC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recentPayments[] = $row;
}
?>

<?php include '../../includes/header.php'; ?>
<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/admin-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <!-- Students Stats -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Étudiants</h3>
                    <span class="text-2xl font-bold text-primary"><?php echo $stats['total_students']; ?></span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Actifs</span>
                        <span class="font-medium"><?php echo $stats['active_students']; ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-primary rounded-full h-2" 
                             style="width: <?php echo $stats['total_students'] > 0 
                                ? ($stats['active_students'] / $stats['total_students'] * 100) 
                                : 0; ?>%">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructors Stats -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Instructeurs</h3>
                    <span class="text-2xl font-bold text-primary"><?php echo $stats['total_instructors']; ?></span>
                </div>
                <div class="mt-4">
                    <a href="/auto-ecole/pages/admin/instructors.php" 
                       class="text-sm text-primary hover:text-accent">
                        Gérer les instructeurs →
                    </a>
                </div>
            </div>

            <!-- Revenue Stats -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Revenus mensuels</h3>
                    <span class="text-2xl font-bold text-green-600">
                        <?php echo number_format($stats['monthly_revenue'], 2); ?> DH
                    </span>
                </div>
                <div class="mt-4">
                    <a href="/auto-ecole/pages/admin/finance.php" 
                       class="text-sm text-primary hover:text-accent">
                        Voir les rapports financiers →
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Enrollments -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Inscriptions récentes</h3>
                        <a href="/auto-ecole/pages/admin/enrollments.php" 
                           class="text-sm text-primary hover:text-accent">
                            Voir tout
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (!empty($recentEnrollments)): ?>
                        <div class="space-y-4">
                            <?php foreach ($recentEnrollments as $enrollment): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($enrollment['student_first_name'] . ' ' . $enrollment['student_last_name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars($enrollment['course_name']); ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo getStatusBadgeClass($enrollment['status']); ?>">
                                        <?php echo getStatusLabel($enrollment['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-center">
                            Aucune inscription récente
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Approbations en attente
                        </h3>
                        <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                            <?php echo $stats['pending_approvals']; ?> en attente
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (!empty($pendingUsers)): ?>
                        <div class="space-y-4">
                            <?php foreach ($pendingUsers as $user): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo getReadableRole($user['role']); ?>
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="approveUser(<?php echo $user['id']; ?>)"
                                                class="px-3 py-1 bg-green-100 text-green-800 rounded-md hover:bg-green-200">
                                            Approuver
                                        </button>
                                        <button onclick="rejectUser(<?php echo $user['id']; ?>)"
                                                class="px-3 py-1 bg-red-100 text-red-800 rounded-md hover:bg-red-200">
                                            Rejeter
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-center">
                            Aucune approbation en attente
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="mt-6 bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Paiements récents</h3>
                    <a href="/auto-ecole/pages/admin/payments.php" 
                       class="text-sm text-primary hover:text-accent">
                        Voir tout
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
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
                                Montant
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentPayments as $payment): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($payment['course_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo number_format($payment['amount'], 2); ?> DH
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo formatDate($payment['payment_date']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php echo getPaymentStatusClass($payment['status']); ?>">
                                        <?php echo getPaymentStatusLabel($payment['status']); ?>
                                    </span>
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
function approveUser(userId) {
    if (confirm('Êtes-vous sûr de vouloir approuver cet utilisateur?')) {
        fetch('/api/users/approve.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Une erreur est survenue. Veuillez réessayer.');
            }
        });
    }
}

function rejectUser(userId) {
    if (confirm('Êtes-vous sûr de vouloir rejeter cet utilisateur?')) {
        fetch('/api/users/reject.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Une erreur est survenue. Veuillez réessayer.');
            }
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>