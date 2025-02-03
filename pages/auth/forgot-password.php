<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);

    if (empty($email)) {
        $errors['email'] = 'L\'email est requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email invalide';
    }

    if (empty($errors)) {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Generate reset token
            $token = generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $stmt = $conn->prepare("
                INSERT INTO password_resets (email, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $email, $token, $expires);

            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors['general'] = 'Une erreur est survenue. Veuillez réessayer.';
            }
        } else {
            // Don't reveal if email exists or not for security
            $success = true;
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Mot de passe oublié
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Entrez votre email pour réinitialiser votre mot de passe
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <?php if ($success): ?>
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                Si un compte existe avec cet email, vous recevrez les instructions de réinitialisation.
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php if (isset($errors['general'])): ?>
                    <div class="rounded-md bg-red-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
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

                <form class="space-y-6" action="" method="POST">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Adresse email
                        </label>
                        <div class="mt-1">
                        <input id="email" name="email" type="email" required
    class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>"
    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <?php if (isset($errors['email'])): ?>
                                <p class="mt-2 text-sm text-red-600"><?php echo $errors['email']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            Envoyer les instructions
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Ou
                        </span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="/pages/auth/login.php" class="font-medium text-primary hover:text-accent">
                        Retour à la connexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>