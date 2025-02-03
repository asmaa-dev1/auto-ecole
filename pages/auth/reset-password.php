<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();
$functions = AppFunctions::getInstance();

$errors = [];
$success = false;
$validToken = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /auto-ecole/pages/auth/login.php');
    exit();
}

// Verify token
$stmt = $conn->prepare("
    SELECT email, expires_at 
    FROM password_resets 
    WHERE token = ? AND used = 0 
    AND expires_at > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $validToken = true;
    $resetData = $result->fetch_assoc();
    $email = $resetData['email'];
} else {
    $errors['token'] = 'Le lien de réinitialisation est invalide ou a expiré.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validation
    if (empty($password)) {
        $errors['password'] = 'Le nouveau mot de passe est requis';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Les mots de passe ne correspondent pas';
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE users 
                SET password = ?,
                    failed_attempts = 0,
                    locked_until = NULL
                WHERE email = ?
            ");
            $stmt->bind_param("ss", $hashedPassword, $email);
            $stmt->execute();

            // Mark token as used
            $stmt = $conn->prepare("
                UPDATE password_resets 
                SET used = 1 
                WHERE token = ?
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $errors['general'] = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Réinitialisation du mot de passe
            </h2>
        </div>

        <?php if ($success): ?>
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            Votre mot de passe a été réinitialisé avec succès.
                        </p>
                        <div class="mt-4">
                            <a href="/auto-ecole/pages/auth/login.php" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-accent">
                                Se connecter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($validToken): ?>
            <?php if (isset($errors['general'])): ?>
                <div class="rounded-md bg-red-50 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                <?php echo $errors['general']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-8 space-y-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Nouveau mot de passe
                    </label>
                    <div class="mt-1">
                        <input type="password" id="password" name="password" required
                               class="input-field <?php echo isset($errors['password']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($errors['password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                        Confirmer le mot de passe
                    </label>
                    <div class="mt-1">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="input-field <?php echo isset($errors['confirm_password']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['confirm_password']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Réinitialiser le mot de passe
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            <?php echo $errors['token']; ?>
                        </p>
                        <div class="mt-4">
                            <a href="/auto-ecole/pages/auth/forgot-password.php" class="text-sm font-medium text-primary hover:text-accent">
                                Demander un nouveau lien
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>