<?php
// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the license type from URL parameter
$type = isset($_GET['type']) ? $_GET['type'] : 'B';

// Define base prices for each license type
$licensePrices = [
    'A' => 3500.00,  // Prix pour Permis Moto
    'A1' => 3000.00, // Prix pour Permis Moto légère
    'B' => 3800.00,  // Prix pour Permis Voiture
    'C' => 6500.00,  // Prix pour Permis Poids Lourd
    'D' => 7500.00,  // Prix pour Permis Bus
    'EC' => 8000.00, // Prix pour Permis Super Lourds
    'ED' => 8500.00  // Prix pour Permis Transport en Commun
];

// Database connection
require_once '../config/database.php';
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Get course information from database
$stmt = $conn->prepare("
    SELECT c.*, c.course_price, lt.name as license_type_name, lt.code as license_type_code 
    FROM courses c
    JOIN license_types lt ON c.license_type_id = lt.id
    WHERE lt.code = ?
    AND c.status = 'active'
    LIMIT 1
");
$stmt->bind_param("s", $type);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

// If no course found, set default values based on license type
if (!$course) {
    // Get the appropriate price for the license type
    $defaultPrice = $licensePrices[$type] ?? 3800.00; // Default to B license price if type not found
    
    $course = [
        'license_type_name' => 'Type ' . $type,
        'description' => getDefaultDescription($type),
        'hours_theory' => getTheoryHours($type),
        'hours_practice' => getPracticeHours($type),
        'course_price' => $defaultPrice,
        'price' => $defaultPrice,
        'name' => 'Permis ' . $type,
        'status' => 'active',
        'id' => null
    ];
}

// Helper functions for different license types
function getTheoryHours($type) {
    $hours = [
        'A' => 20,
        'A1' => 20,
        'B' => 30,
        'C' => 40,
        'D' => 45,
        'EC' => 45,
        'ED' => 45
    ];
    return $hours[$type] ?? 30;
}

function getPracticeHours($type) {
    $hours = [
        'A' => 20,
        'A1' => 18,
        'B' => 25,
        'C' => 30,
        'D' => 35,
        'EC' => 40,
        'ED' => 40
    ];
    return $hours[$type] ?? 25;
}

function getDefaultDescription($type) {
    switch($type) {
        case 'A':
            return 'Formation complète pour la conduite de motos de toutes cylindrées. Inclut la théorie et la pratique sur circuit.';
        case 'A1':
            return 'Formation pour la conduite de motos légères jusqu\'à 125cc. Idéal pour les débutants.';
        case 'B':
            return 'Formation standard pour la conduite de voitures particulières. Programme complet théorique et pratique.';
        case 'C':
            return 'Formation professionnelle pour la conduite de poids lourds. Inclut les aspects techniques et réglementaires.';
        case 'D':
            return 'Formation spécialisée pour le transport de passagers. Focus sur la sécurité et le service client.';
        case 'EC':
            return 'Formation avancée pour les véhicules super lourds et les remorques. Technique de conduite spécialisée.';
        case 'ED':
            return 'Formation pour le transport en commun de grande capacité. Accent sur la responsabilité et la sécurité.';
        default:
            return 'Formation professionnelle adaptée à vos besoins.';
    }
}

// Include the header
require_once '../includes/header.php';
?>

<!-- Course Details Section -->
<section class="py-20">
    <div class="container mx-auto px-4">
        <!-- Course Header -->
        <div class="text-center mb-12">
            <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas <?php echo $type === 'B' ? 'fa-car' : ($type === 'A' ? 'fa-motorcycle' : ($type === 'C' ? 'fa-truck' : 'fa-bus')); ?> text-4xl text-primary"></i>
            </div>
            <h1 class="text-4xl font-bold mb-4">
                Permis <?php echo htmlspecialchars($type); ?> - <?php echo htmlspecialchars($course['license_type_name']); ?>
            </h1>
            <p class="text-xl text-gray-600"><?php echo htmlspecialchars($course['description']); ?></p>
        </div>

        <!-- Course Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <!-- Requirements -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-semibold mb-6">Conditions Requises</h2>
                <ul class="space-y-4">
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Âge minimum : <?php echo $type === 'B' ? '18' : ($type === 'A' ? '18' : ($type === 'C' ? '21' : '23')); ?> ans</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Examen médical valide</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Carte d'identité nationale</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Photos d'identité récentes</span>
                    </li>
                    <?php if ($type === 'C' || $type === 'D'): ?>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Permis B obtenu depuis <?php echo $type === 'C' ? '2' : '3'; ?> ans</span>
                    </li>
                    <?php endif; ?>
                    <?php if ($type === 'D'): ?>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Casier judiciaire vierge</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- What's Included -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-semibold mb-6">Ce Qui Est Inclus</h2>
                <ul class="space-y-4">
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span><?php echo $course['hours_theory']; ?> heures de formation théorique</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span><?php echo $course['hours_practice']; ?> heures de formation pratique</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Support de cours complet</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Tests blancs illimités</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Accompagnement à l'examen</span>
                    </li>
                    <?php if ($type === 'A'): ?>
                    <li class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span>Équipement de sécurité fourni</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Course Details -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Prix de la Formation</h3>
                    <p class="text-3xl font-bold text-primary">
                        <?php echo number_format($course['course_price'] ?? $licensePrices[$type], 2); ?> DH
                    </p>
                    <p class="text-sm text-gray-500 mt-2">
                        Prix incluant la formation complète, matériel pédagogique et accompagnement à l'examen
                    </p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Durée Totale</h3>
                    <p class="text-gray-600">
                        <span class="text-2xl font-bold text-primary">
                            <?php echo $course['hours_theory'] + $course['hours_practice']; ?>
                        </span> heures de formation
                    </p>
                    <div class="mt-2 text-sm text-gray-500">
                        <div class="flex items-center mb-1">
                            <i class="fas fa-chalkboard-teacher mr-2"></i>
                            <span><?php echo $course['hours_theory']; ?> heures de théorie</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-car mr-2"></i>
                            <span><?php echo $course['hours_practice']; ?> heures de pratique</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="text-center">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/auto-ecole/pages/candidate/courses.php" 
                   class="inline-block px-8 py-4 bg-primary text-white rounded-lg hover:bg-accent transition-colors font-semibold">
                    S'inscrire à Cette Formation
                </a>
            <?php else: ?>
                <a href="/auto-ecole/pages/auth/register.php" 
                   class="inline-block px-8 py-4 bg-primary text-white rounded-lg hover:bg-accent transition-colors font-semibold">
                    Créer un Compte pour S'inscrire
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>