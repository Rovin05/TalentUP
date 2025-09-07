<?php
// This script is the single source of truth for user authentication.
// Every protected page must include this file at the very top.

// --- Function to log messages for debugging session issues ---
function session_log_message($message) {
    $log_dir = dirname(__DIR__) . '/logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0775, true);
    }
    $log_file = $log_dir . 'auth_log.txt';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - [SESSION_CHECK] " . $message . "\n", FILE_APPEND);
}

// Ensure the session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

session_log_message("--- Running Security Check ---");
session_log_message("Page: " . basename($_SERVER['PHP_SELF']));
session_log_message("Session ID: " . session_id());
session_log_message("Session Data: " . json_encode($_SESSION));

// --- THE ACTUAL SECURITY CHECK ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    session_log_message("Authentication check FAILED. Redirecting to login.html.");
    
    // Clear any potentially corrupted session data
    session_unset();
    session_destroy();

    // Redirect to the login page
    header('Location: login.html');
    exit(); // IMPORTANT: Stop script execution immediately after redirect
}

session_log_message("Authentication check PASSED for user: " . $_SESSION['username']);

// If the script reaches here, the user is authenticated.
// We can now define the $currentUser variable for the page that included this script.
$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role']
];
?>
