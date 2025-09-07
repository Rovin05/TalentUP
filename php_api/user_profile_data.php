<?php
// --- Enhanced Error Reporting for Development ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();

// --- Centralized Response Handling ---
function send_json_error($statusCode, $message, $logMessage = '') {
    http_response_code($statusCode);
    // In a real production environment, you might log the detailed error but only show a generic message to the user.
    if (!empty($logMessage)) {
        error_log($logMessage);
    }
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// --- Session Check ---
if (!isset($_SESSION['user_id'])) {
    send_json_error(401, 'User not authenticated.');
}

// --- Database Connection ---
// This will cause a fatal error if the file is not found. Ensure 'db_connect.php' is in the same directory.
require 'db_connect.php'; 

// Check if the connection was successful
if (!$conn || $conn->connect_error) {
    send_json_error(500, 'Database connection failed. Please check server configuration.', 'DB Connection Error: ' . ($conn->connect_error ?? 'Unknown error'));
}


$userId = $_SESSION['user_id'];
$response = ['success' => false];

try {
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    if (!$stmt) {
        send_json_error(500, 'Failed to prepare database query.', 'SQL Prepare Error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    
    if (!$stmt->execute()) {
        send_json_error(500, 'Failed to execute database query.', 'SQL Execute Error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['user'] = $user;
    } else {
        send_json_error(404, 'User not found in the database.');
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode($response);

} catch (Exception $e) {
    send_json_error(500, 'An unexpected server error occurred.', 'General Exception: ' . $e->getMessage());
}
?>

