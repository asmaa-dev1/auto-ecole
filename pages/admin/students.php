<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Initialize auth and check role
$auth = Auth::getInstance();
$auth->requireLogin();
$auth->checkRole('admin');

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Vérification si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Récupération des étudiants
$stmt = $conn->prepare("
    SELECT 
        u.id, u.first_name, u.last_name, u.email, u.phone, u.status, u.created_at,
        COUNT(DISTINCT e.id) as total_enrollments
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.candidate_id
    WHERE u.role = 'candidate'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php include '../../includes/header.php'; ?>


<div class="min-h-screen bg-gray-100">
    <?php include '../../includes/admin-sidebar.php'; ?>
    
    <div class="ml-64 p-8">
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - Administration</title>
    <link href="../../assets/css/tailwind.css" rel="stylesheet">
    <link href="../../assets/css/fontawesome.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../../includes/admin-sidebar.php'; ?>
    
    <div class="">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Gestion des Étudiants</h1>
            <button onclick="openAddModal()" class="bg-primary text-white px-4 py-2 rounded-lg">
                <i class="fas fa-plus mr-2"></i>Ajouter un étudiant
            </button>
        </div>
        
        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form class="flex gap-4">
                <input type="text" placeholder="Rechercher..." class="border rounded px-3 py-2 w-64">
                <select class="border rounded px-3 py-2">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                    Filtrer
                </button>
            </form>
        </div>
        
        <!-- Liste des étudiants -->
        <div class="bg-white rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left">Nom</th>
                            <th class="px-6 py-3 text-left">Email</th>
                            <th class="px-6 py-3 text-left">Téléphone</th>
                            <th class="px-6 py-3 text-left">Inscriptions</th>
                            <th class="px-6 py-3 text-left">Statut</th>
                            <th class="px-6 py-3 text-left">Date d'inscription</th>
                            <th class="px-6 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr class="border-t" data-student-id="<?php echo $student['id']; ?>">
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($student['email']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td class="px-6 py-4"><?php echo $student['total_enrollments']; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-sm <?php echo $student['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo date('d/m/Y', strtotime($student['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="editStudent(<?php echo $student['id']; ?>)" class="text-blue-500 hover:underline">Modifier</button>
                                <button onclick="deleteStudent(<?php echo $student['id']; ?>)" class="text-red-500 hover:underline ml-2">Supprimer</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="addModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-[500px]">
        <h2 class="text-lg font-bold mb-4">Ajouter un étudiant</h2>
        <form>
            <div class="grid grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">
                        <i class="fas fa-user mr-2"></i>Prénom
                    </label>
                    <input type="text" class="border rounded w-full px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Prénom">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">
                        <i class="fas fa-user mr-2"></i>Nom
                    </label>
                    <input type="text" class="border rounded w-full px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Nom">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    <i class="fas fa-envelope mr-2"></i>Adresse email
                </label>
                <input type="email" class="border rounded w-full px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Email">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    <i class="fas fa-phone mr-2"></i>Numéro de téléphone
                </label>
                <input type="tel" class="border rounded w-full px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Téléphone">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    <i class="fas fa-calendar mr-2"></i>Date de naissance
                </label>
                <input type="date" class="border rounded w-full px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    <i class="fas fa-lock mr-2"></i>Mot de passe
                </label>
                <input type="password" class="border rounded w-full px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Mot de passe">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition" onclick="closeAddModal()">
                    <i class="fas fa-times mr-2"></i>Annuler
                </button>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Ajouter
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }
        
        function editStudent(id) {
            // Add logic to edit student
            alert('Edit student ' + id);
        }
        
        function deleteStudent(id) {
            if (confirm('Voulez-vous vraiment supprimer cet étudiant ?')) {
                // Add logic to delete student
                alert('Student ' + id + ' deleted.');
            }
        }
    </script>
</body>
</html>
