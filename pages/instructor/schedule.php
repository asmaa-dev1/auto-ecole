<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Get database connection
$db = new DatabaseConnection();
$conn = $db->getConnection();

// Initialize Auth and Functions
$auth = Auth::getInstance();
$functions = AppFunctions::getInstance();

// Ensure user is logged in and is an instructor
$auth->requireLogin();
$auth->checkRole('instructor');

// Get current week's start and end dates
$week = isset($_GET['week']) ? new DateTime($_GET['week']) : new DateTime();
$week->modify('monday this week');
$weekStart = clone $week;
$weekEnd = clone $week;
$weekEnd->modify('sunday this week');

// Get all sessions for the week
$stmt = $conn->prepare("
    SELECT 
        s.*,
        c.name as course_name,
        u.first_name as student_first_name,
        u.last_name as student_last_name,
        u.profile_image as student_profile_image
    FROM sessions s
    JOIN enrollments e ON s.enrollment_id = e.id
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON e.candidate_id = u.id
    WHERE e.instructor_id = ?
    AND s.session_date BETWEEN ? AND ?
    ORDER BY s.session_date ASC, s.start_time ASC
");

$stmt->bind_param("iss", 
    $_SESSION['user_id'],
    $weekStart->format('Y-m-d'),
    $weekEnd->format('Y-m-d')
);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Organize sessions by day and time
$weekSchedule = [];
for ($i = 0; $i < 7; $i++) {
    $currentDate = clone $weekStart;
    $currentDate->modify("+$i days");
    $weekSchedule[$currentDate->format('Y-m-d')] = [];
}

foreach ($sessions as $session) {
    $weekSchedule[$session['session_date']][] = $session;
}

// Get time slots for the schedule
$timeSlots = [];
$startHour = 8; // 8:00
$endHour = 18;  // 18:00
for ($hour = $startHour; $hour <= $endHour; $hour++) {
    $timeSlots[] = sprintf('%02d:00', $hour);
    if ($hour != $endHour) {
        $timeSlots[] = sprintf('%02d:30', $hour);
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/instructor-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <!-- Calendar Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Mon Planning</h1>
                <p class="text-sm text-gray-600">
                    Semaine du <?php echo $weekStart->format('d/m/Y'); ?> 
                    au <?php echo $weekEnd->format('d/m/Y'); ?>
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <a href="?week=<?php echo $weekStart->modify('-1 week')->format('Y-m-d'); ?>"
                   class="px-3 py-2 bg-white rounded-lg shadow-sm hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="?week=<?php echo (new DateTime())->format('Y-m-d'); ?>"
                   class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-accent">
                    Aujourd'hui
                </a>
                <a href="?week=<?php echo $weekStart->modify('+2 weeks')->format('Y-m-d'); ?>"
                   class="px-3 py-2 bg-white rounded-lg shadow-sm hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="/auto-ecole/pages/instructor/schedule-session.php"
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvelle session
                </a>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="grid grid-cols-8 divide-x divide-gray-200">
                <!-- Time Column -->
                <div class="bg-gray-50">
                    <div class="h-16 border-b border-gray-200"></div>
                    <?php foreach ($timeSlots as $timeSlot): ?>
                        <div class="h-12 border-b border-gray-200 px-2 py-1">
                            <span class="text-xs font-medium text-gray-500">
                                <?php echo $timeSlot; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Days Columns -->
                <?php
                $today = new DateTime();
                for ($i = 0; $i < 7; $i++):
                    $currentDate = clone $weekStart;
                    $currentDate->modify("+$i days");
                    $isToday = $currentDate->format('Y-m-d') === $today->format('Y-m-d');
                ?>
                    <div class="relative">
                        <!-- Day Header -->
                        <div class="h-16 border-b border-gray-200 p-2 <?php echo $isToday ? 'bg-blue-50' : 'bg-gray-50'; ?>">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo $currentDate->format('l'); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <?php echo $currentDate->format('d/m'); ?>
                            </p>
                        </div>

                        <!-- Time Slots -->
                        <?php foreach ($timeSlots as $timeSlot): ?>
                            <div class="h-12 border-b border-gray-200 relative">
                                <?php
                                $currentDateTime = $currentDate->format('Y-m-d') . ' ' . $timeSlot;
                                foreach ($weekSchedule[$currentDate->format('Y-m-d')] as $session):
                                    $sessionStart = strtotime($session['session_date'] . ' ' . $session['start_time']);
                                    $sessionEnd = strtotime($session['session_date'] . ' ' . $session['end_time']);
                                    $currentSlotTime = strtotime($currentDateTime);
                                    
                                    if ($currentSlotTime >= $sessionStart && $currentSlotTime < $sessionEnd):
                                ?>
                                    <div class="absolute inset-x-0 rounded-lg mx-1 p-1
                                        <?php echo $session['session_type'] === 'theory' ? 'bg-blue-100' : 'bg-green-100'; ?>">
                                        <div class="flex items-center space-x-2">
                                            <img src="<?php echo $session['student_profile_image'] 
                                                ? '/assets/images/profiles/' . htmlspecialchars($session['student_profile_image'])
                                                : 'https://ui-avatars.com/api/?name=' . urlencode($session['student_first_name'] . ' ' . $session['student_last_name']); ?>"
                                                class="w-6 h-6 rounded-full" alt="Profile">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium truncate">
                                                    <?php echo htmlspecialchars($session['student_first_name'] . ' ' . $session['student_last_name']); ?>
                                                </p>
                                                <p class="text-xs truncate">
                                                    <?php echo $session['session_type'] === 'theory' ? 'Théorie' : 'Pratique'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>