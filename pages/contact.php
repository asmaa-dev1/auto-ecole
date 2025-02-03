<?php
$pageTitle = "Contact";
require_once '../includes/header.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');

    // Validate form data
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Veuillez entrer une adresse email valide.";
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            // Send email notification (you'll need to configure your email settings)
            $to = "contact@auto-ecole.ma";
            $email_subject = "Nouveau message de contact - $subject";
            $email_body = "Nom: $name\nEmail: $email\nTéléphone: $phone\n\nMessage:\n$message";
            $headers = "From: $email";
            
            mail($to, $email_subject, $email_body, $headers);
            
            $success_message = "Votre message a été envoyé avec succès. Nous vous contacterons bientôt.";
            
            // Clear form data after successful submission
            $name = $email = $phone = $subject = $message = '';
        } catch (PDOException $e) {
            error_log("Error saving contact message: " . $e->getMessage());
            $error_message = "Une erreur est survenue. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Auto École</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<!-- Hero Section -->
<section class="bg-primary text-white py-20">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold mb-4">Contactez-nous</h1>
        <p class="text-xl">Nous sommes là pour répondre à toutes vos questions</p>
    </div>
</section>

<!-- Contact Information -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-3 gap-8 mb-12">
            <!-- Address -->
            <div class="text-center p-6 bg-gray-50 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <div class="text-primary mb-4">
                    <i class="fas fa-map-marker-alt text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Notre Adresse</h3>
                <p>Zone Industrielle Zenata<br>Casablanca, Maroc</p>
            </div>
            
            <!-- Phone -->
            <div class="text-center p-6 bg-gray-50 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <div class="text-primary mb-4">
                    <i class="fas fa-phone text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Téléphone</h3>
                <p class="text-lg">+212 632804247</p>
                <p class="text-sm text-gray-600 mt-2">Lun-Sam: 8h-18h</p>
            </div>
            
            <!-- Email -->
            <div class="text-center p-6 bg-gray-50 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <div class="text-primary mb-4">
                    <i class="fas fa-envelope text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Email</h3>
                <p class="text-lg">contact@auto-ecole.ma</p>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="max-w-2xl mx-auto">
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="/auto-ecole/pages/contact.php" method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-gray-700 font-medium mb-2">Nom complet *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
                               required>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-gray-700 font-medium mb-2">Email *</label>
                        <input type="email" 
                               id="email" 
                               name="email"
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
                               required>
                    </div>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-gray-700 font-medium mb-2">Téléphone</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone"
                           value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
                           pattern="[0-9+\s-]*">
                </div>

                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-gray-700 font-medium mb-2">Sujet</label>
                    <select id="subject" 
                            name="subject"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        <option value="general" <?php echo ($subject ?? '') === 'general' ? 'selected' : ''; ?>>
                            Renseignement général
                        </option>
                        <option value="inscription" <?php echo ($subject ?? '') === 'inscription' ? 'selected' : ''; ?>>
                            Inscription
                        </option>
                        <option value="tarifs" <?php echo ($subject ?? '') === 'tarifs' ? 'selected' : ''; ?>>
                            Tarifs
                        </option>
                        <option value="autre" <?php echo ($subject ?? '') === 'autre' ? 'selected' : ''; ?>>
                            Autre
                        </option>
                    </select>
                </div>

                <!-- Message -->
                <div>
                    <label for="message" class="block text-gray-700 font-medium mb-2">Message *</label>
                    <textarea id="message" 
                              name="message"
                              rows="5"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:outline-none resize-none"
                              required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <button type="submit" 
                        class="w-full bg-primary text-white py-3 px-6 rounded-lg hover:bg-accent transition-colors duration-300">
                    Envoyer le message
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold mb-8 text-center">Notre Localisation</h2>
            <div class="rounded-lg overflow-hidden shadow-lg">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13296.014653793654!2d-7.5138763266276045!3d33.61673329999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda7cb7b3e3b1dd7%3A0x3ef9898f8bd00be9!2sZenata%2C%20Casablanca!5e0!3m2!1sfr!2sma!4v1705020445211!5m2!1sfr!2sma"
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>

