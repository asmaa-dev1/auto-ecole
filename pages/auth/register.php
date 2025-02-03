<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

$auth = Auth::getInstance();
if ($auth->isLoggedIn()) {
    header('Location: /auto-ecole/index.php');
    exit();
}

if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = sanitizeInput($_POST['password']);
    $confirmPassword = sanitizeInput($_POST['confirm_password']);
    $role = sanitizeInput($_POST['role']);
    $dateOfBirth = sanitizeInput($_POST['date_of_birth']);

    // Validation
    if (empty($firstName)) {
        $errors['first_name'] = 'Le prénom est requis';
    }

    if (empty($lastName)) {
        $errors['last_name'] = 'Le nom est requis';
    }

    if (empty($email)) {
        $errors['email'] = 'L\'email est requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email invalide';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Le numéro de téléphone est requis';
    }

    if (empty($password)) {
        $errors['password'] = 'Le mot de passe est requis';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Les mots de passe ne correspondent pas';
    }

    if (empty($dateOfBirth)) {
        $errors['date_of_birth'] = 'La date de naissance est requise';
    }

    // Check if email already exists
    if (empty($errors['email'])) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'Cet email est déjà utilisé';
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashedPassword = hashPassword($password);
        $status = 'pending';

        $stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, email, phone, password, role, date_of_birth, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("ssssssss", 
            $firstName, 
            $lastName, 
            $email, 
            $phone, 
            $hashedPassword, 
            $role,
            $dateOfBirth,
            $status
        );

        if ($stmt->execute()) {
            $success = true;
            // Clear form data after successful submission
            $_POST = array();
        } else {
            $errors['general'] = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Créer un compte
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Ou
            <a href="/auto-ecole/pages/auth/login.php" class="font-medium text-primary hover:text-accent">
                connectez-vous à votre compte existant
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <?php if ($success): ?>
                <div class="rounded-md bg-green-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                Inscription réussie! Vous pouvez maintenant vous connecter.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
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
                <div class="grid grid-cols-1 gap-6">
                    <!-- Role Selection -->
                    <div class="col-span-1">
                        <label for="role" class="block text-sm font-medium text-gray-700">Type de compte</label>
                        <div class="mt-1 grid grid-cols-2 gap-3">
                            <label class="relative flex">
                                <input type="radio" name="role" value="candidate" 
                                    <?php echo (!isset($_POST['role']) || $_POST['role'] === 'candidate') ? 'checked' : ''; ?>
                                    class="sr-only peer">
                                <div class="w-full p-4 text-gray-600 rounded-lg border border-gray-300 cursor-pointer peer-checked:border-primary peer-checked:text-primary hover:text-gray-600 hover:bg-gray-50">
                                    <div class="flex items-center justify-center">
                                        <i class="fas fa-user-graduate text-xl mr-2"></i>
                                        <span class="font-medium">Candidat</span>
                                    </div>
                                </div>
                            </label>
                            <label class="relative flex">
                                <input type="radio" name="role" value="instructor" 
                                    <?php echo (isset($_POST['role']) && $_POST['role'] === 'instructor') ? 'checked' : ''; ?>
                                    class="sr-only peer">
                                <div class="w-full p-4 text-gray-600 rounded-lg border border-gray-300 cursor-pointer peer-checked:border-primary peer-checked:text-primary hover:text-gray-600 hover:bg-gray-50">
                                    <div class="flex items-center justify-center">
                                        <i class="fas fa-chalkboard-teacher text-xl mr-2"></i>
                                        <span class="font-medium">Instructeur</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Name Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">
                                Prénom
                            </label>
                            <input type="text" name="first_name" id="first_name"
                                class="block w-full px-3 py-2 border-2 border-gray-300 rounded-md <?php echo isset($errors['first_name']) ? 'border-red-500' : ''; ?>"
                                value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            <?php if (isset($errors['first_name'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['first_name']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">
                                Nom
                            </label>
                            <input type="text" name="last_name" id="last_name"
                                class="block w-full px-3 py-2 border-2 border-gray-300 rounded-md <?php echo isset($errors['first_name']) ? 'border-red-500' : ''; ?>"
                                value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            <?php if (isset($errors['last_name'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['last_name']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Adresse email
                        </label>
                        <input type="email" name="email" id="email"
                            class="block w-full px-3 py-2 border-2 border-gray-300 rounded-md <?php echo isset($errors['first_name']) ? 'border-red-500' : ''; ?>"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <?php if (isset($errors['email'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['email']; ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            Numéro de téléphone
                        </label>
                        <input type="tel" name="phone" id="phone"
                            class="block w-full px-3 py-2 border-2 border-gray-300 rounded-md <?php echo isset($errors['first_name']) ? 'border-red-500' : ''; ?>"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        <?php if (isset($errors['phone'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['phone']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">
                            Date de naissance
                        </label>
                        <input type="date" name="date_of_birth" id="date_of_birth"
                            class="block w-full px-3 py-2 border-2 border-gray-300 rounded-md <?php echo isset($errors['first_name']) ? 'border-red-500' : ''; ?>"
                            value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                        <?php if (isset($errors['date_of_birth'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['date_of_birth']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Password Fields -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Mot de passe
                        </label>
                        <input type="password" name="password" id="password"
                            class="block w-full px-3 py-2 border-2 border-gray-300 rounded-md <?php echo isset($errors['first_name']) ? 'border-red-500' : ''; ?>"
                        <?php if (isset($errors['password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            Confirmer le mot de passe
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password"
                            class="block w-full px-3 py-2 border-2 border-gray-300 rounded-md <?php echo isset($errors['first_name']) ? 'border-red-500' : ''; ?>"
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['confirm_password']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="flex items-center">
                        <input type="checkbox" name="terms" id="terms" required
                            class="h-4 w-4 text-primary focus:ring-accent border-gray-300 rounded">
                        <label for="terms" class="ml-2 block text-sm text-gray-900">
                            J'accepte les <a href="#" class="text-primary hover:text-accent">conditions d'utilisation</a>
                            et la <a href="#" class="text-primary hover:text-accent">politique de confidentialité</a>
                        </label>
                    </div>
                </div>

                <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Créer un compte
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Ou s'inscrire avec
                        </span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div>
                        <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.29 18.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0020 3.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.073 4.073 0 01.8 7.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84"></path>
                            </svg>
                        </a>
                    </div>

                    <div>
                        <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Client-side validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const terms = document.getElementById('terms');

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas');
            return;
        }

        if (!terms.checked) {
            e.preventDefault();
            alert('Veuillez accepter les conditions d\'utilisation');
            return;
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>