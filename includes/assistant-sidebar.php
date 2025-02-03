<?php
// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->

<aside class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg pt-20">
    <div class="px-4 py-6">
        <!-- Profile Section -->
        <div class="flex items-center mb-6 px-2">
            <img src="<?php echo $functions->getUserProfileImage($_SESSION['user_id']); ?>" 
                alt="Profile" 
                class="w-10 h-10 rounded-full">
            <div class="ml-3">
                <p class="font-medium text-gray-800">
                    <?php echo $functions->getUserFullName($_SESSION['user_id']); ?>
                </p>
                <p class="text-sm text-gray-500">Assistant</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="dashboard.php" 
               class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg 
                      <?php echo $currentPage === 'dashboard.php' 
                          ? 'text-white bg-primary' 
                          : 'text-gray-900 hover:bg-gray-100'; ?>">
                <i class="fas fa-chart-line w-5 h-5 mr-3"></i>
                Tableau de bord
            </a>

            <!-- Candidates Management -->
            <a href="/auto-ecole/pages/admin/add-instructor.php" 
               class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg 
                      <?php echo $currentPage === 'candidates.php' 
                          ? 'text-white bg-primary' 
                          : 'text-gray-900 hover:bg-gray-100'; ?>">
                <i class="fas fa-users w-5 h-5 mr-3"></i>
                
                Candidats
                
                <?php if ($pendingCount > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                        <?php echo $pendingCount; ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Session Management -->
            <a href="management.php" 
               class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg 
                      <?php echo $currentPage === 'management.php' 
                          ? 'text-white bg-primary' 
                          : 'text-gray-900 hover:bg-gray-100'; ?>">
                <i class="fas fa-calendar-alt w-5 h-5 mr-3"></i>
                Gestion des sessions
            </a>

            <!-- Reports -->
            <a href="reports.php" 
               class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg 
                      <?php echo $currentPage === 'reports.php' 
                          ? 'text-white bg-primary' 
                          : 'text-gray-900 hover:bg-gray-100'; ?>">
                <i class="fas fa-file-alt w-5 h-5 mr-3"></i>
                Rapports
            </a>

            <hr class="my-6 border-gray-200">

            <!-- Settings -->
            <a href="settings.php" 
               class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg 
                      <?php echo $currentPage === 'settings.php' 
                          ? 'text-white bg-primary' 
                          : 'text-gray-900 hover:bg-gray-100'; ?>">
                <i class="fas fa-cog w-5 h-5 mr-3"></i>
                Paramètres
            </a>

            <!-- Logout -->
            <a href="../auth/logout.php" 
               class="flex items-center px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg">
                <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                Déconnexion
            </a>
        </nav>
    </div>
</aside>