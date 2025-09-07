<?php
// Start the session to access its functions.
session_start();

// 1. Unset all of the session variables.
$_SESSION = [];

// 2. If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session.
session_destroy();

// Respond with a success message.
header('Content-Type: application/json');
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
?>
