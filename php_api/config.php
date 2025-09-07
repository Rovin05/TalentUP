<?php
/**
 * Central Configuration File
 *
 * This file should be included at the VERY TOP of every PHP script in your API.
 * It handles secure session initialization, error reporting, and database connection.
 */

// --- 1. ERROR REPORTING ---
// Show all errors during development.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 2. SECURE SESSION MANAGEMENT ---
// This is the core fix for your session issues.
// These settings ensure session cookies are sent securely and are not accessible by JavaScript.
$cookie_params = [
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => '', // Your domain name, e.g., 'talentup.lk'. Leave empty for localhost.
    'secure' => isset($_SERVER['HTTPS']), // Only send cookie over HTTPS
    'httponly' => true, // Prevent JavaScript access to the session cookie
    'samesite' => 'Strict' // Prevent CSRF attacks
];

session_set_cookie_params($cookie_params);
session_name('TalentUpSID'); // Use a custom session name for better security
session_start();

// --- 3. DATABASE CONNECTION (Example using PDO) ---
// Replace with your actual database credentials.
define('DB_HOST', 'localhost');
define('DB_NAME', 'talentup_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // For a real application, you might want to log this error instead of displaying it.
    // Never display detailed database errors in production.
    die("Database connection failed: " . $e->getMessage());
}

// --- 4. JSON RESPONSE HELPER FUNCTION ---
// A helper function to standardize JSON responses.
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

?>
