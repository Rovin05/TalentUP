<?php
/**
 * db_connect.php
 * This file establishes a secure connection to the MySQL database
 * and sets up universal configurations for API responses.
 */

// --- Error Reporting ---
// Report all errors during development. Turn this off in production.
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Configuration ---
$db_host = 'localhost';     // Usually 'localhost' for XAMPP
$db_user = 'root';          // Default XAMPP username
$db_pass = '';              // Default XAMPP password is empty
$db_name = 'talentup_db';   // The name of your database

// --- Establish Connection ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// --- Connection Check ---
// If the connection fails, stop everything and send a clean JSON error.
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Database connection failed: " . $conn->connect_error
    ]);
    exit; // Stop script execution immediately.
}

// --- Set Character Set ---
// Set the character set to utf8mb4 to support emojis and other special characters.
if (!$conn->set_charset("utf8mb4")) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Error loading character set utf8mb4: " . $conn->error
    ]);
    exit;
}

// NOTE: session_start() and header('Content-Type: application/json')
// have been moved to the individual API scripts to ensure they are called correctly.
?>

