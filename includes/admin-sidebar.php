<!-- Admin Sidebar -->
<div class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg pt-20">
    <nav class="mt-5 px-2">
        <!-- Dashboard -->
        <a href="/auto-ecole/pages/admin/dashboard.php" 
           class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                  <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/dashboard.php') !== false ? 'bg-gray-100' : ''; ?>">
            <i class="fas fa-tachometer-alt w-6 text-primary"></i>
            <span>Tableau de bord</span>
        </a>

        <!-- Users Management -->
        <div class="mt-4">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Utilisateurs
            </p>
            <div class="mt-2 space-y-1">
                    <a href="/auto-ecole/pages/admin/users.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/instructors.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher w-6 text-primary"></i>
                    <span>Instructeurs</span>
                </a>
                <a href="/auto-ecole/pages/admin/students.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/students.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-user-graduate w-6 text-primary"></i>
                    <span>Étudiants</span>
                </a>
                <a href="/auto-ecole/pages/admin/assistants.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/assistants.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-user-tie w-6 text-primary"></i>
                    <span>Assistants</span>
                </a>
            </div>
        </div>

        <!-- Course Management -->
        <div class="mt-4">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Formations
            </p>
            <div class="mt-2 space-y-1">
                <a href="/auto-ecole/pages/admin/courses.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/courses.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-book w-6 text-primary"></i>
                    <span>Cours</span>
                </a>
                <a href="/auto-ecole/pages/admin/enrollments.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/enrollments.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-user-plus w-6 text-primary"></i>
                    <span>Inscriptions</span>
                </a>
            </div>
        </div>

        <!-- Finance Management -->
        <div class="mt-4">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Finance
            </p>
            <div class="mt-2 space-y-1">
                <a href="/auto-ecole/pages/admin/payments.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/payments.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-credit-card w-6 text-primary"></i>
                    <span>Paiements</span>
                </a>
                <a href="/auto-ecole/pages/admin/finance.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/finance.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-chart-line w-6 text-primary"></i>
                    <span>Rapports</span>
                </a>
            </div>
        </div>

        <!-- Settings -->
        <div class="mt-4">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Paramètres
            </p>
            <div class="mt-2 space-y-1">
                <a href="/auto-ecole/pages/admin/settings.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/settings.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-cog w-6 text-primary"></i>
                    <span>Configuration</span>
                </a>
                <a href="/auto-ecole/pages/admin/profile.php"
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/profile.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-user-circle w-6 text-primary"></i>
                    <span>Profil</span>
                </a>
            </div>
        </div>

        <!-- Logout -->
        <div class="mt-6 px-4">
        <a href="/auto-ecole/logout.php" 
               class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span class="ml-3">Déconnexion</span>
            </a>
        </div>
    </nav>
</div>