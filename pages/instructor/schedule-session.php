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

$errors = [];
$success = false;

// Get all active students for this instructor
$stmt = $conn->prepare("
    SELECT 
        u.id as student_id,
        u.first_name,
        u.last_name,
        e.id as enrollment_id,
        c.name as course_name
    FROM enrollments e
    JOIN users u ON e.candidate_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE e.instructor_id = ?
    AND e.status = 'active'
    ORDER BY u.first_name, u.last_name
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Pre-select student if provided in URL
$selectedStudentId = $_GET['student_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = sanitizeInput($_POST['student_id']);
    $sessionType = sanitizeInput($_POST['session_type']);
    $sessionDate = sanitizeInput($_POST['session_date']);
    $startTime = sanitizeInput($_POST['start_time']);
    $endTime = sanitizeInput($_POST['end_time']);
    $notes = sanitizeInput($_POST['notes']);

    // Validation
    if (empty($studentId)) {
        $errors['student_id'] = 'Veuillez sélectionner un étudiant';
    }

    if (empty($sessionType)) {
        $errors['session_type'] = 'Veuillez sélectionner un type de session';
    }

    if (empty($sessionDate)) {
        $errors['session_date'] = 'La date est requise';
    } elseif (strtotime($sessionDate) < strtotime('today')) {
        $errors['session_date'] = 'La date ne peut pas être dans le passé';
    }

    if (empty($startTime)) {
        $errors['start_time'] = 'L\'heure de début est requise';
    }

    if (empty($endTime)) {
        $errors['end_time'] = 'L\'heure de fin est requise';
    } elseif ($endTime <= $startTime) {
        $errors['end_time'] = 'L\'heure de fin doit être après l\'heure de début';
    }

    // Check for scheduling conflicts
    if (empty($errors)) {
        // Get enrollment ID for the selected student
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE candidate_id = ? AND instructor_id = ? AND status = 'active'");
        $stmt->bind_param("ii", $studentId, $_SESSION['user_id']);
        $stmt->execute();
        $enrollmentId = $stmt->get_result()->fetch_assoc()['id'];

        // Check for conflicts
        $stmt = $conn->prepare("
            SELECT COUNT(*) as conflict_count
            FROM sessions
            WHERE enrollment_id IN (
                SELECT id FROM enrollments WHERE instructor_id = ?
            )
            AND session_date = ?
            AND (
                (start_time <= ? AND end_time >= ?) OR
                (start_time <= ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
            AND status != 'cancelled'
        ");
        $stmt->bind_param("isssssss", 
            $_SESSION['user_id'], 
            $sessionDate, 
            $startTime, $startTime,
            $endTime, $endTime,
            $startTime, $endTime
        );
        $stmt->execute();
        $conflicts = $stmt->get_result()->fetch_assoc()['conflict_count'];

        if ($conflicts > 0) {
            $errors['general'] = 'Il y a un conflit d\'horaire avec une autre session';
        } else {
            // Create session
            $stmt = $conn->prepare("
                INSERT INTO sessions (
                    enrollment_id, session_type, session_date, 
                    start_time, end_time, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'scheduled')
            ");
            $stmt->bind_param("isssss", 
                $enrollmentId, $sessionType, $sessionDate,
                $startTime, $endTime, $notes
            );

            if ($stmt->execute()) {
                $success = true;
                $_POST = array(); // Clear form
            } else {
                $errors['general'] = 'Une erreur est survenue lors de la création de la session';
            }
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include '../../includes/instructor-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 pt-20">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">
                        Programmer une nouvelle session
                    </h2>
                </div>

                <div class="p-6">
                    <?php if ($success): ?>
                        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">
                                        La session a été programmée avec succès.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($errors['general'])): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <?php echo $errors['general']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <!-- Student Selection -->
                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700">
                                Étudiant
                            </label>
                            <select name="student_id" id="student_id" 
                                    class="mt-1 input-field <?php echo isset($errors['student_id']) ? 'border-red-500' : ''; ?>">
                                <option value="">Sélectionner un étudiant</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['student_id']; ?>"
                                        <?php echo ($selectedStudentId == $student['student_id'] || (isset($_POST['student_id']) && $_POST['student_id'] == $student['student_id'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> - 
                                        <?php echo htmlspecialchars($student['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['student_id'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['student_id']; ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Session Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Type de session
                            </label>
                            <div class="mt-1 grid grid-cols-2 gap-3">
                                <label class="relative flex">
                                    <input type="radio" name="session_type" value="theory" 
                                        <?php echo (!isset($_POST['session_type']) || $_POST['session_type'] === 'theory') ? 'checked' : ''; ?>
                                        class="sr-only peer">
                                    <div class="w-full p-4 text-gray-600 rounded-lg border border-gray-300 cursor-pointer 
                                              peer-checked:border-primary peer-checked:text-primary hover:bg-gray-50">
                                        <div class="flex items-center justify-center">
                                            <i class="fas fa-book text-xl mr-2"></i>
                                            <span>Théorie</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="relative flex">
                                    <input type="radio" name="session_type" value="practice"
                                        <?php echo (isset($_POST['session_type']) && $_POST['session_type'] === 'practice') ? 'checked' : ''; ?>
                                        class="sr-only peer">
                                    <div class="w-full p-4 text-gray-600 rounded-lg border border-gray-300 cursor-pointer 
                                              peer-checked:border-primary peer-checked:text-primary hover:bg-gray-50">
                                        <div class="flex items-center justify-center">
                                            <i class="fas fa-car text-xl mr-2"></i>
                                            <span>Pratique</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php if (isset($errors['session_type'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['session_type']; ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Date and Time -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="session_date" class="block text-sm font-medium text-gray-700">
                                    Date
                                </label>
                                <input type="date" name="session_date" id="session_date"
                                    class="mt-1 input-field <?php echo isset($errors['session_date']) ? 'border-red-500' : ''; ?>"
                                    value="<?php echo isset($_POST['session_date']) ? htmlspecialchars($_POST['session_date']) : ''; ?>">
                                <?php if (isset($errors['session_date'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['session_date']; ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="start_time" class="block text-sm font-medium text-gray-700">
                                        Heure de début
                                    </label>
                                    <input type="time" name="start_time" id="start_time"
                                        class="mt-1 input-field <?php echo isset($errors['start_time']) ? 'border-red-500' : ''; ?>"
                                        value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : ''; ?>">
                                    <?php if (isset($errors['start_time'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo $errors['start_time']; ?></p>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <label for="end_time" class="block text-sm font-medium text-gray-700">
                                        Heure de fin
                                    </label>
                                    <input type="time" name="end_time" id="end_time"
                                        class="mt-1 input-field <?php echo isset($errors['end_time']) ? 'border-red-500' : ''; ?>"
                                        value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : ''; ?>">
                                    <?php if (isset($errors['end_time'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo $errors['end_time']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700">
                                Notes (optionnel)
                            </label>
                            <textarea name="notes" id="notes" rows="3" 
                                class="mt-1 input-field"
                                placeholder="Ajoutez des notes sur la session..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end space-x-3">
                            <a href="/auto-ecole/pages/instructor/students.php" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Annuler
                            </a>
                            <button type="submit"
                                    class="px-4 py-2 bg-primary text-white rounded-md hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Programmer la session
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>