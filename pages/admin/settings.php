<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/Settings.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$auth = Auth::getInstance();
$auth->requireLogin();
$auth->checkRole('admin');

$errors = [];
$success = false;

// Get settings instance
$settingsManager = Settings::getInstance();
$settings = $settingsManager->getAll();

// Default values if settings are not set
$defaultSettings = [
    'site_name' => 'Auto École',
    'site_email' => '',
    'contact_phone' => '',
    'address' => '',
    'schedule_start_time' => '08:00',
    'schedule_end_time' => '18:00',
    'max_students_per_instructor' => 10,
    'maintenance_mode' => 0
];

// Merge defaults with stored settings
$settings = array_merge($defaultSettings, $settings);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update settings
    $newSettings = [
        'site_name' => sanitizeInput($_POST['site_name']),
        'site_email' => sanitizeInput($_POST['site_email']),
        'contact_phone' => sanitizeInput($_POST['contact_phone']),
        'address' => sanitizeInput($_POST['address']),
        'schedule_start_time' => sanitizeInput($_POST['schedule_start_time']),
        'schedule_end_time' => sanitizeInput($_POST['schedule_end_time']),
        'max_students_per_instructor' => (int)$_POST['max_students_per_instructor'],
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
    ];

    // Validation
    if (empty($newSettings['site_name'])) {
        $errors['site_name'] = 'Le nom du site est requis';
    }

    if (empty($newSettings['site_email'])) {
        $errors['site_email'] = 'L\'email du site est requis';
    } elseif (!filter_var($newSettings['site_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['site_email'] = 'Format d\'email invalide';
    }

    if (empty($errors)) {
        try {
            if ($settingsManager->update($newSettings)) {
                $success = true;
                $settings = $newSettings;
            } else {
                $errors['general'] = 'Une erreur est survenue lors de la mise à jour des paramètres';
            }
        } catch (Exception $e) {
            $errors['general'] = 'Erreur: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <?php include '../../includes/admin-sidebar.php'; ?>

    <div class="ml-64 p-8 pt-20">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Paramètres du système</h2>

                <?php if ($success): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">Paramètres mis à jour avec succès</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($errors['general'])): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?php echo $errors['general']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <!-- Site Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du site</h3>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="site_name" class="block text-sm font-medium text-gray-700">
                                    Nom du site
                                </label>
                                <input type="text" id="site_name" name="site_name" 
                                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none <?php echo isset($errors['site_name']) ? 'border-red-500' : ''; ?>"
                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                                <?php if (isset($errors['site_name'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['site_name']; ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="site_email" class="block text-sm font-medium text-gray-700">
                                    Email du site
                                </label>
                                <input type="email" id="site_email" name="site_email" 
                                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none<?php echo isset($errors['site_email']) ? 'border-red-500' : ''; ?>"
                                       value="<?php echo htmlspecialchars($settings['site_email']); ?>">
                                <?php if (isset($errors['site_email'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['site_email']; ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-700">
                                    Téléphone de contact
                                </label>
                                <input type="tel" id="contact_phone" name="contact_phone" 
                                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                       value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                            </div>

                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">
                                    Adresse
                                </label>
                                <textarea id="address" name="address" rows="3" 
                                          class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres de planification</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="schedule_start_time" class="block text-sm font-medium text-gray-700">
                                    Heure de début
                                </label>
                                <input type="time" id="schedule_start_time" name="schedule_start_time" 
                                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                       value="<?php echo htmlspecialchars($settings['schedule_start_time']); ?>">
                            </div>

                            <div>
                                <label for="schedule_end_time" class="block text-sm font-medium text-gray-700">
                                    Heure de fin
                                </label>
                                <input type="time" id="schedule_end_time" name="schedule_end_time" 
                                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                       value="<?php echo htmlspecialchars($settings['schedule_end_time']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres système</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="max_students_per_instructor" class="block text-sm font-medium text-gray-700">
                                    Nombre maximum d'étudiants par instructeur
                                </label>
                                <input type="number" id="max_students_per_instructor" name="max_students_per_instructor" 
                                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                       value="<?php echo htmlspecialchars($settings['max_students_per_instructor']); ?>">
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                       <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                <label for="maintenance_mode" class="ml-2 block text-sm text-gray-900">
                                    Mode maintenance
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Sauvegarder les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>