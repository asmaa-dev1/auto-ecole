<?php
// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the header
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-primary to-accent text-white py-20">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <div class="md:w-1/2 mb-10 md:mb-0">
                <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
                    Votre Succès Commence Ici
                </h1>
                <p class="text-lg md:text-xl mb-8 text-gray-100">
                    Formation professionnelle de conduite avec des instructeurs certifiés. 
                    Obtenez votre permis en toute confiance.
                </p>
                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <a href="/auto-ecole/pages/auth/register.php" 
                       class="px-8 py-3 bg-white text-primary rounded-lg hover:bg-gray-100 transition-colors text-center font-semibold">
                        Commencer
                    </a>
                    <a href="#courses" 
                       class="px-8 py-3 border-2 border-white text-white rounded-lg hover:bg-white hover:text-primary transition-colors text-center font-semibold">
                        Nos Formations
                    </a>
                </div>
            </div>
            <div class="md:w-1/2">
                <img src="/auto-ecole/assets/images/hero-car.png" alt="Formation de conduite" class="rounded-lg shadow-xl">
            </div>
        </div>
    </div>
</section>

<!-- License Types Section -->
<section id="courses" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl md:text-4xl font-bold text-center mb-12">Nos Types de Permis</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Automobile Card -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6 border border-gray-100">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-car text-2xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Permis B</h3>
                <p class="text-gray-600 mb-6">Formation complète pour la conduite automobile. Cours théoriques et pratiques.</p>
                <a href="/auto-ecole/pages/course-info.php?type=B" 
                   class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors text-sm">
                    En savoir plus
                </a>
            </div>

            <!-- Motorcycle Card -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6 border border-gray-100">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-motorcycle text-2xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Permis A</h3>
                <p class="text-gray-600 mb-6">Formation spécialisée pour la conduite de motos. Sécurité et maîtrise.</p>
                <a href="/auto-ecole/pages/course-info.php?type=A" 
                   class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors text-sm">
                    En savoir plus
                </a>
            </div>

            <!-- Truck Card -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6 border border-gray-100">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-truck text-2xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Permis C</h3>
                <p class="text-gray-600 mb-6">Formation professionnelle pour la conduite de poids lourds.</p>
                <a href="/auto-ecole/pages/course-info.php?type=C" 
                   class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors text-sm">
                    En savoir plus
                </a>
            </div>

            <!-- Bus Card -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6 border border-gray-100">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-bus text-2xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Permis D</h3>
                <p class="text-gray-600 mb-6">Formation spécialisée pour le transport de passagers.</p>
                <a href="/auto-ecole/pages/course-info.php?type=D" 
                   class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-accent transition-colors text-sm">
                    En savoir plus
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl md:text-4xl font-bold text-center mb-12">Pourquoi Nous Choisir?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-6">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-medal text-3xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Instructeurs Certifiés</h3>
                <p class="text-gray-600">Une équipe d'instructeurs professionnels et expérimentés pour vous guider.</p>
            </div>

            <div class="text-center p-6">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-car text-3xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Véhicules Modernes</h3>
                <p class="text-gray-600">Une flotte de véhicules récents et bien entretenus pour votre formation.</p>
            </div>

            <div class="text-center p-6">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-graduation-cap text-3xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Taux de Réussite Élevé</h3>
                <p class="text-gray-600">Un excellent taux de réussite grâce à notre méthode d'enseignement éprouvée.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-primary">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-8">Prêt à Commencer Votre Formation?</h2>
        <p class="text-xl text-white/90 mb-10 max-w-2xl mx-auto">
            Inscrivez-vous dès aujourd'hui et commencez votre journey vers l'obtention de votre permis de conduire.
        </p>
        <a href="/auto-ecole/pages/auth/register.php" 
           class="inline-block px-8 py-4 bg-white text-primary rounded-lg hover:bg-gray-100 transition-colors font-semibold">
            S'inscrire Maintenant
        </a>
    </div>
</section>

<!-- Footer -->
<footer class="bg-secondary text-white py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h4 class="text-lg font-semibold mb-4">Auto Écol</h4>
                <p class="text-gray-400">
                    Formation professionnelle de conduite depuis plus de 20 ans.
                </p>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">Liens Rapides</h4>
                <ul class="space-y-2">
                    <li><a href="/auto-ecole" class="text-gray-400 hover:text-white transition-colors">Accueil</a></li>
                    <li><a href="/auto-ecole/pages/about.php" class="text-gray-400 hover:text-white transition-colors">À Propos</a></li>
                    <li><a href="/auto-ecole/pages/contact.php" class="text-gray-400 hover:text-white transition-colors">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">Nos Services</h4>
                <ul class="space-y-2">
                    <li><a href="/auto-ecole/pages/course-info.php?type=B" class="text-gray-400 hover:text-white transition-colors">Permis B</a></li>
                    <li><a href="/auto-ecole/pages/course-info.php?type=A" class="text-gray-400 hover:text-white transition-colors">Permis A</a></li>
                    <li><a href="/auto-ecole/pages/course-info.php?type=C" class="text-gray-400 hover:text-white transition-colors">Permis C</a></li>
                    <li><a href="/auto-ecole/pages/course-info.php?type=D" class="text-gray-400 hover:text-white transition-colors">Permis D</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">Contact</h4>
                <ul class="space-y-2">
                    <li class="flex items-center space-x-2">
                        <i class="fas fa-map-marker-alt text-accent"></i>
                        <span class="text-gray-400">123 Rue Example, Ville</span>
                    </li>
                    <li class="flex items-center space-x-2">
                        <i class="fas fa-phone text-accent"></i>
                        <span class="text-gray-400">+212 632804247</span>
                    </li>
                    <li class="flex items-center space-x-2">
                        <i class="fas fa-envelope text-accent"></i>
                        <span class="text-gray-400">contact@auto-ecole.com</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center">
            <p class="text-gray-400">© 2025 Asmaa El Hint. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<!-- Mobile Menu JavaScript -->
<script>
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!mobileMenuButton.contains(e.target) && !mobileMenu.contains(e.target)) {
            mobileMenu.classList.add('hidden');
        }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
                // Close mobile menu after clicking a link
                mobileMenu.classList.add('hidden');
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>