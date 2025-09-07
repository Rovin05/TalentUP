<?php
// --- SETUP AND INITIALIZATION ---

// Start output buffering to prevent any premature output from interfering with headers.
ob_start();

// Ensure a session is started. This should be at the very top of any script using sessions.
if (session_status() == PHP_SESSION_NONE) {
    // Recommended session settings for security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Set the response content type to JSON for consistent API behavior.
header('Content-Type: application/json');

// Include the database connection file.
require 'db_connect.php';

// --- HELPER FUNCTION FOR LOGGING ---
/**
 * Logs a message to a file for debugging purposes.
 * @param string $message The message to log.
 */
function log_message($message) {
    $log_dir = dirname(__DIR__) . '/logs/';
    if (!is_dir($log_dir)) {
        // Attempt to create the directory if it doesn't exist.
        mkdir($log_dir, 0775, true);
    }
    $log_file = $log_dir . 'auth_log.txt';
    // Append the message with a timestamp.
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// --- MAIN LOGIC ---
try {
    // --- 1. DATA INTAKE AND VALIDATION ---
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Provide a fallback for urlencoded form data if JSON decoding fails or is empty.
    if (json_last_error() !== JSON_ERROR_NONE || !$input) {
        $input = $_POST;
    }

    $identifier = isset($input['identifier']) ? trim($input['identifier']) : null;
    $password = isset($input['password']) ? $input['password'] : null;

    if (empty($identifier) || empty($password)) {
        throw new Exception("Email/Username and Password are required.");
    }

    // --- 2. DATABASE QUERY ---
    // Use a prepared statement to prevent SQL injection.
    $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
    
    $sql = $is_email 
        ? "SELECT id, username, email, password, role FROM users WHERE email = ? LIMIT 1" 
        : "SELECT id, username, email, password, role FROM users WHERE username = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        log_message("Database prepare statement failed: " . $conn->error);
        throw new Exception("A server error occurred. Please try again later.");
    }
    
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // --- 3. PASSWORD VERIFICATION AND SESSION CREATION ---
    if ($user && password_verify($password, $user['password'])) {
        // --- Successful Login ---
        
        // Regenerate the session ID to prevent session fixation attacks.
        session_regenerate_id(true); 
        
        // Store user data in the session.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // The session data is now set. The client-side redirect will handle navigation.
        log_message("Login success for user: {$user['username']}");

        echo json_encode([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'user' => [ // Send back non-sensitive user info for the client if needed.
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);

    } else {
        // --- Failed Login ---
        log_message("Login failed for identifier: {$identifier}");
        // Use a generic message to avoid revealing whether the username or password was wrong.
        throw new Exception("Invalid credentials provided.");
    }

} catch (Exception $e) {
    // --- ERROR HANDLING ---
    // Set an appropriate HTTP response code for client-side errors.
    http_response_code(401); // Unauthorized
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    // --- CLEANUP ---
    if (isset($conn)) {
        $conn->close();
    }
    // Flush the output buffer, sending the final JSON response to the client.
    ob_end_flush();
}
?>
