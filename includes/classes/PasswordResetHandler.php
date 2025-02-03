<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/mailer.php';

class PasswordResetHandler {
    private $conn;
    private $errors = [];
    private $success = false;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $this->sanitizeEmail($_POST['email']);
            
            if ($this->validateEmail($email)) {
                $this->processResetRequest($email);
            }
        }

        return [
            'success' => $this->success,
            'errors' => $this->errors
        ];
    }

    private function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    private function validateEmail($email) {
        if (empty($email)) {
            $this->errors['email'] = 'L\'email est requis';
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Format d\'email invalide';
            return false;
        }
        
        return true;
    }

    private function processResetRequest($email) {
        try {
            // Vérifier si l'email existe
            $user = $this->getUserByEmail($email);
            
            if ($user) {
                // Vérifier s'il y a déjà une demande récente
                if ($this->hasRecentRequest($email)) {
                    $this->errors['general'] = 'Une demande récente existe déjà. Veuillez attendre avant de réessayer.';
                    return;
                }

                // Créer et stocker le token
                $token = $this->createResetToken($email);
                
                if ($token) {
                    // Envoyer l'email
                    $this->sendResetEmail($email, $token, $user['first_name']);
                }
            }
            
            // Pour la sécurité, toujours indiquer succès même si l'email n'existe pas
            $this->success = true;
            
        } catch (Exception $e) {
            $this->logError($e);
            $this->errors['general'] = 'Une erreur est survenue. Veuillez réessayer plus tard.';
        }
    }

    private function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id, first_name FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function hasRecentRequest($email) {
        $stmt = $this->conn->prepare("
            SELECT 1 FROM password_resets 
            WHERE email = ? 
            AND created_at > NOW() - INTERVAL 15 MINUTE 
            AND used = 0
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function createResetToken($email) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $this->conn->prepare("
            INSERT INTO password_resets (email, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $email, $token, $expires);
        
        return $stmt->execute() ? $token : false;
    }

    private function sendResetEmail($email, $token, $firstName) {
        $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/pages/auth/reset-password.php?token=" . $token;
        
        $subject = "Réinitialisation de votre mot de passe - Auto École";
        
        $message = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2>Bonjour {$firstName},</h2>
                <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
                <p>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :</p>
                <p>
                    <a href='{$resetLink}' style='display: inline-block; padding: 10px 20px; background-color: #1E40AF; color: white; text-decoration: none; border-radius: 5px;'>
                        Réinitialiser mon mot de passe
                    </a>
                </p>
                <p>Ce lien expirera dans 1 heure.</p>
                <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>
                <p>Cordialement,<br>L'équipe Auto École</p>
            </body>
            </html>
        ";

        try {
            $mailer = new Mailer();
            $mailer->sendEmail($email, $subject, $message);
        } catch (Exception $e) {
            $this->logError($e);
            throw new Exception('Erreur lors de l\'envoi de l\'email');
        }
    }

    private function logError($exception) {
        // Log l'erreur dans un fichier
        error_log(date('Y-m-d H:i:s') . " - Password Reset Error: " . $exception->getMessage() . "\n", 3, "../../logs/password_reset.log");
    }
}