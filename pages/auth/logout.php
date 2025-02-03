<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Make sure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Initialize auth instance
$auth = Auth::getInstance();

// Log the logout activity if user was logged in
if ($auth->isLoggedIn()) {
    // Get user info before destroying session
    $userId = $_SESSION['user_id'];
    
    // Log the logout activity
    $functions = AppFunctions::getInstance();
}

// Perform logout
$auth->logout();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies that might have been set
// Add any application-specific cookies that need to be cleared
setcookie('remember_me', '', time() - 3600, '/');

// Optional: Clear any other session storage or cached data
// This depends on your application's specific needs

// Redirect to login page with a logged out message
// Using urlencode to safely pass the message in the URL
$message = urlencode("Vous avez été déconnecté avec succès.");
header("Location: /auto-ecole/pages/auth/login.php?message=" . $message);
exit();