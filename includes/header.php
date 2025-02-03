<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Helper functions for the header
function getUserProfileImage($userId) {
    try {
        $user = AppFunctions::getInstance()->getUserById($userId);
        if ($user && isset($user['profile_image'])) {
            return '/assets/images/profiles/' . htmlspecialchars($user['profile_image']);
        }
        return 'https://ui-avatars.com/api/?name=User';
    } catch (Exception $e) {
        error_log("Error getting user profile image: " . $e->getMessage());
        return 'https://ui-avatars.com/api/?name=User';
    }
}

function getUserFullName($userId) {
    try {
        $user = AppFunctions::getInstance()->getUserById($userId);
        if ($user && isset($user['first_name']) && isset($user['last_name'])) {
            return htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        }
        return 'Utilisateur';
    } catch (Exception $e) {
        error_log("Error getting user full name: " . $e->getMessage());
        return 'Utilisateur';
    }
}

function getUnreadNotificationsCount() {
    try {
        if (!isset($_SESSION['user_id'])) return 0;
        $notifications = AppFunctions::getInstance()->getUnreadNotifications($_SESSION['user_id']);
        return is_array($notifications) ? count($notifications) : 0;
    } catch (Exception $e) {
        error_log("Error getting unread notifications: " . $e->getMessage());
        return 0;
    }
}

function isLoggedIn() {
    return Auth::getInstance()->isLoggedIn();
}

// Get user data once for the header
$currentUser = null;
if (isLoggedIn() && isset($_SESSION['user_id'])) {
    try {
        $currentUser = AppFunctions::getInstance()->getUserById($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Error getting current user data: " . $e->getMessage());
    }
}

// Get dashboard link based on role
$dashboardLink = '/auto-ecole/pages/';
if (isset($_SESSION['role'])) {
    $dashboardLink .= match($_SESSION['role']) {
        'admin' => 'admin/dashboard.php',
        'instructor' => 'instructor/dashboard.php',
        'candidate' => 'candidate/dashboard.php',
        default => 'dashboard.php'
    };
}
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Auto École - Formation professionnelle de conduite avec des instructeurs certifiés">
    <meta name="theme-color" content="#1E40AF">
    
    <title><?php echo isset($pageTitle) ? "$pageTitle - Auto École" : "Auto École - Formation de Qualité"; ?></title>
    
    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Include Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    
    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1E40AF',
                        secondary: '#1E293B',
                        accent: '#3B82F6',
                        success: '#059669',
                        warning: '#D97706',
                        danger: '#DC2626'
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif']
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                        '128': '32rem'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'slide-down': 'slideDown 0.3s ease-out'
                    }
                }
            },
            plugins: []
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 5rem;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #1E40AF;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #3B82F6;
        }
        
        .notification-badge {
            @apply absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="fixed w-full top-0 z-50 bg-white shadow-md">
        <nav class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Logo Section -->
                <div class="flex items-center space-x-4">
                    <a href="/auto-ecole" class="flex items-center space-x-2">
                        <img src="/auto-ecole/assets/images/logo.png" alt="Auto École Logo" class="h-12 w-auto">
                        <span class="text-xl font-bold text-primary">Auto École</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/auto-ecole" class="text-gray-700 hover:text-primary transition-colors">Accueil</a>
                    <a href="/auto-ecole/pages/about.php" class="text-gray-700 hover:text-primary transition-colors">À Propos</a>
                    <a href="/auto-ecole/pages/contact.php" class="text-gray-700 hover:text-primary transition-colors">Contact</a>
                    
                    <?php if (isLoggedIn() && $currentUser): ?>
                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2">
                                <img src="<?php echo getUserProfileImage($_SESSION['user_id']); ?>" 
                                     alt="Profile" 
                                     class="w-8 h-8 rounded-full object-cover">
                                <span class="text-gray-700"><?php echo getUserFullName($_SESSION['user_id']); ?></span>
                                <i class="fas fa-chevron-down text-sm text-gray-500"></i>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div x-show="open" 
                                 @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                                
                                <a href="<?php echo $dashboardLink; ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-tachometer-alt mr-2"></i>
                                    Tableau de bord
                                </a>
                                <a href="/auto-ecole/pages/profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>
                                    Profil
                                </a>
                                
                                <a href="/auto-ecole/pages/notifications.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 relative">
                                    <i class="fas fa-bell mr-2"></i>
                                    Notifications
                                    <?php if (getUnreadNotificationsCount() > 0): ?>
                                        <span class="notification-badge"><?php echo getUnreadNotificationsCount(); ?></span>
                                    <?php endif; ?>
                                </a>
                                
                                <hr class="my-2 border-gray-200">
                                
                                <a href="/auto-ecole/pages/auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>
                                    Déconnexion
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center space-x-4">
                            <a href="/auto-ecole/pages/auth/login.php" 
                               class="px-4 py-2 text-primary hover:text-accent transition-colors">
                                Connexion
                            </a>
                            <a href="/auto-ecole/pages/auth/register.php" 
                               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                                Inscription
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden text-gray-700 hover:text-primary" 
                        id="mobile-menu-button"
                        aria-label="Toggle mobile menu">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <div class="md:hidden hidden" id="mobile-menu">
                <div class="flex flex-col space-y-4 mt-4 pb-4 animate-fade-in">
                    <a href="/auto-ecole" class="text-gray-700 hover:text-primary transition-colors">Accueil</a>
                    <a href="/auto-ecole/pages/about.php" class="text-gray-700 hover:text-primary transition-colors">À Propos</a>
                    <a href="/auto-ecole/pages/contact.php" class="text-gray-700 hover:text-primary transition-colors">Contact</a>
                    
                    <?php if (isLoggedIn() && $currentUser): ?>
                        <hr class="border-gray-200">
                        <a href="<?php echo $dashboardLink; ?>" class="text-gray-700 hover:text-primary transition-colors">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Tableau de bord
                        </a>
                        <a href="/auto-ecole/pages/profile.php" class="text-gray-700 hover:text-primary transition-colors">
                            <i class="fas fa-user mr-2"></i>
                            Profil
                        </a>
                        <a href="/auto-ecole/pages/notifications.php" class="text-gray-700 hover:text-primary transition-colors relative">
                            <i class="fas fa-bell mr-2"></i>
                            Notifications
                            <?php if (getUnreadNotificationsCount() > 0): ?>
                                <span class="notification-badge"><?php echo getUnreadNotificationsCount(); ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/auto-ecole/pages/auth/logout.php" class="text-red-600 hover:text-red-700 transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Déconnexion
                        </a>
                    <?php else: ?>
                        <div class="flex flex-col space-y-2">
                            <a href="/auto-ecole/pages/auth/login.php" 
                               class="px-4 py-2 text-primary hover:text-accent transition-colors">
                                Connexion
                            </a>
                            <a href="/auto-ecole/pages/auth/register.php" 
                               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors text-center">
                                Inscription
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="mt-20">

<script>
// Mobile menu handler
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuButton.addEventListener('click', function(e) {
        e.preventDefault();
        mobileMenu.classList.toggle('hidden');
    });
});

// Auth form handler
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form[action*="login"]');
    const registerForm = document.querySelector('form[action*="register"]');

    function handleAuthForm(form) {
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    // Handle error
                    alert(data.message || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue');
            });
        });
    }

    handleAuthForm(loginForm);
    handleAuthForm(registerForm);
});   
</script>