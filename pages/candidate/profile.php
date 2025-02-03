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
$auth->checkRole('candidate');

// Get user info
$user = getUserById($_SESSION['user_id']);
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $city = sanitizeInput($_POST['city']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($firstName)) {
        $errors['first_name'] = 'Le prénom est requis';
    }

    if (empty($lastName)) {
        $errors['last_name'] = 'Le nom est requis';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Le numéro de téléphone est requis';
    }

    // Handle password change if requested
    if (!empty($currentPassword)) {
        if (!verifyPassword($currentPassword, $user['password'])) {
            $errors['current_password'] = 'Mot de passe actuel incorrect';
        } elseif (empty($newPassword)) {
            $errors['new_password'] = 'Le nouveau mot de passe est requis';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Les mots de passe ne correspondent pas';
        }
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
            $errors['profile_image'] = 'Format d\'image non valide. Utilisez JPG ou PNG.';
        } elseif ($_FILES['profile_image']['size'] > $maxSize) {
            $errors['profile_image'] = 'L\'image ne doit pas dépasser 5MB';
        } else {
            $uploadDir = '../../assets/images/profiles/';
            $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                $errors['profile_image'] = 'Erreur lors du téléchargement de l\'image';
            }
        }
    }

    // Update profile if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE users 
            SET first_name = ?, 
                last_name = ?, 
                phone = ?, 
                address = ?,
                city = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param("sssssi", 
            $firstName, 
            $lastName, 
            $phone, 
            $address,
            $city,
            $_SESSION['user_id']
        );

        if ($stmt->execute()) {
            // Update password if changed
            if (!empty($newPassword)) {
                $hashedPassword = hashPassword($newPassword);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $_SESSION['user_id']);
                $stmt->execute();
            }

            // Update profile image if uploaded
            if (isset($fileName)) {
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->bind_param("si", $fileName, $_SESSION['user_id']);
                $stmt->execute();
            }

            $success = true;
            $user = getUserById($_SESSION['user_id']); // Refresh user data
        } else {
            $errors['general'] = 'Une erreur est survenue lors de la mise à jour du profil';
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/candidate-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Mon Profil</h2>

                <?php if ($success): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    Profil mis à jour avec succès!
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Profile Image -->
                    <div class="flex items-center space-x-6">
                        <div class="relative">
                            <img src="<?php echo $user['profile_image'] 
                                ? '/assets/images/profiles/' . htmlspecialchars($user['profile_image'])
                                : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']); ?>" 
                                alt="Profile" 
                                class="w-24 h-24 rounded-full object-cover">
                            <label for="profile_image" class="absolute bottom-0 right-0 bg-white rounded-full p-1 shadow-lg cursor-pointer">
                                <i class="fas fa-camera text-gray-600"></i>
                                <input type="file" id="profile_image" name="profile_image" class="hidden" accept="image/*">
                            </label>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Photo de profil</h3>
                            <p class="text-sm text-gray-500">JPG ou PNG. 5MB max.</p>
                            <?php if (isset($errors['profile_image'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['profile_image']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="grid grid-cols-1 gap-6 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">
                                    Prénom
                                </label>
                                <input type="text" id="first_name" name="first_name" 
                                    class="mt-1 input-field <?php echo isset($errors['first_name']) ? 'border-red-500' : ''; ?>"
                                    value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                <?php if (isset($errors['first_name'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['first_name']; ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">
                                    Nom
                                </label>
                                <input type="text" id="last_name" name="last_name" 
                                    class="mt-1 input-field <?php echo isset($errors['last_name']) ? 'border-red-500' : ''; ?>"
                                    value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                <?php if (isset($errors['last_name'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['last_name']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email
                            </label>
                            <input type="email" id="email" 
                                class="mt-1 input-field bg-gray-50" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" 
                                disabled>
                            <p class="mt-1 text-sm text-gray-500">
                                L'email ne peut pas être modifié. Contactez l'administration pour tout changement.
                            </p>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                Téléphone
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                class="mt-1 input-field <?php echo isset($errors['phone']) ? 'border-red-500' : ''; ?>"
                                value="<?php echo htmlspecialchars($user['phone']); ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['phone']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700">
                                Adresse
                            </label>
                            <input type="text" id="address" name="address" 
                                class="mt-1 input-field"
                                value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">
                                Ville
                            </label>
                            <input type="text" id="city" name="city" 
                                class="mt-1 input-field"
                                value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Changer le mot de passe</h3>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">
                                    Mot de passe actuel
                                </label>
                                <input type="password" id="current_password" name="current_password" 
                                    class="mt-1 input-field <?php echo isset($errors['current_password']) ? 'border-red-500' : ''; ?>">
                                <?php if (isset($errors['current_password'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['current_password']; ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">
                                    Nouveau mot de passe
                                </label>
                                <input type="password" id="new_password" name="new_password" 
                                    class="mt-1 input-field <?php echo isset($errors['new_password']) ? 'border-red-500' : ''; ?>">
                                <?php if (isset($errors['new_password'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['new_password']; ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                                    Confirmer le nouveau mot de passe
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                    class="mt-1 input-field <?php echo isset($errors['confirm_password']) ? 'border-red-500' : ''; ?>">
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['confirm_password']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            Sauvegarder les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Preview profile image before upload
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('img').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>