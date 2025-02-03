<!-- Instructor Sidebar -->
<div class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg pt-20">
    <nav class="mt-5 px-2">
        <!-- Dashboard -->
        <a href="/auto-ecole/pages/instructor/dashboard.php" 
           class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                  <?php echo strpos($_SERVER['REQUEST_URI'], '/instructor/dashboard.php') !== false ? 'bg-gray-100' : ''; ?>">
            <i class="fas fa-tachometer-alt w-6 text-primary"></i>
            <span>Tableau de bord</span>
        </a>

        <!-- Students Management -->
        <div class="mt-4">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Étudiants
            </p>
            <div class="mt-2 space-y-1">
                <a href="/auto-ecole/pages/instructor/students.php" 
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/instructor/students.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-users w-6 text-primary"></i>
                    <span>Gestion des étudiants</span>
                </a>
                <a href="/auto-ecole/pages/instructor/student-progress.php" 
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/instructor/student-progress.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-chart-line w-6 text-primary"></i>
                    <span>Progression des étudiants</span>
                </a>
            </div>
        </div>

        <!-- Sessions Management -->
        <div class="mt-4">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Sessions
            </p>
            <div class="mt-2 space-y-1">
                <a href="/auto-ecole/pages/instructor/schedule-session.php" 
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/instructor/schedule-session.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-calendar-check w-6 text-primary"></i>
                    <span>Programmer des sessions</span>
                </a>
                <a href="/auto-ecole/pages/instructor/sessions.php" 
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/instructor/sessions.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-list w-6 text-primary"></i>
                    <span>Liste des sessions</span>
                </a>
            </div>
        </div>

        <!-- Evaluations Management -->
        <div class="mt-4">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Évaluations
            </p>
            <div class="mt-2 space-y-1">
                <a href="/auto-ecole/pages/instructor/evaluations.php" 
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/instructor/evaluations.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-clipboard-list w-6 text-primary"></i>
                    <span>Gestion des évaluations</span>
                </a>
            </div>
        </div>

        <!-- Profile -->
        <div class="mt-4">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Profil
            </p>
            <div class="mt-2 space-y-1">
                <a href="/auto-ecole/pages/instructor/profile.php" 
                   class="group flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                          <?php echo strpos($_SERVER['REQUEST_URI'], '/instructor/profile.php') !== false ? 'bg-gray-100' : ''; ?>">
                    <i class="fas fa-user-circle w-6 text-primary"></i>
                    <span>Mon profil</span>
                </a>
            </div>
        </div>

        <!-- Logout -->
        <div class="mt-6 px-4">
            <a href="/auto-ecole/auto-ecole/pages/auth/logout.php" 
               class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </nav>
</div>