<div class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg pt-16">
    <div class="px-4 py-6">
        <div class="mb-6">
            <?php $user = AppFunctions::getInstance()->getUserById($_SESSION['user_id']); ?>
            <div class="flex items-center">
                <img src="<?php echo $user['profile_image'] ? '/auto-ecole/assets/images/profiles/' . htmlspecialchars($user['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']); ?>"
                     alt="Profile" class="w-12 h-12 rounded-full object-cover">
                <div class="ml-3">
                    <p class="text-gray-800 font-medium">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </p>
                    <p class="text-gray-500 text-sm">Candidat</p>
                </div>
            </div>
        </div>

        <nav class="space-y-2">
            <a href="/auto-ecole/pages/candidate/dashboard.php" 
               class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                      <?php echo str_ends_with($_SERVER['PHP_SELF'], '/dashboard.php') ? 'bg-gray-100' : ''; ?>">
                <i class="fas fa-tachometer-alt w-5"></i>
                <span class="ml-3">Tableau de bord</span>
            </a>

            <a href="/auto-ecole/pages/candidate/courses.php" 
               class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                      <?php echo str_ends_with($_SERVER['PHP_SELF'], '/courses.php') ? 'bg-gray-100' : ''; ?>">
                <i class="fas fa-book w-5"></i>
                <span class="ml-3">Mes cours</span>
            </a>

            <a href="/auto-ecole/pages/candidate/schedule.php" 
               class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                      <?php echo str_ends_with($_SERVER['PHP_SELF'], '/schedule.php') ? 'bg-gray-100' : ''; ?>">
                <i class="fas fa-calendar-alt w-5"></i>
                <span class="ml-3">Planning</span>
            </a>

            <a href="/auto-ecole/pages/candidate/profile.php" 
               class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors
                      <?php echo str_ends_with($_SERVER['PHP_SELF'], '/profile.php') ? 'bg-gray-100' : ''; ?>">
                <i class="fas fa-user w-5"></i>
                <span class="ml-3">Profil</span>
            </a>

            <a href="/auto-ecole/logout.php" 
               class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span class="ml-3">Déconnexion</span>
            </a>
        </nav>
    </div>
</div>