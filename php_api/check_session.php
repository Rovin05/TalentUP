<?php
// This is the endpoint your index.php's JavaScript calls to check if a user is logged in.
require_once 'config.php'; // Must be included to access the session.

header('Content-Type: application/json');

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // User is logged in.
    // Fetch user details from the DB to ensure they are up-to-date.
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, email, role FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            echo json_encode([
                'loggedIn' => true,
                'user' => $user
            ]);
        } else {
            // User in session not found in DB (e.g., deleted), so destroy session.
            session_destroy();
            echo json_encode(['loggedIn' => false]);
        }
    } catch (PDOException $e) {
        echo json_encode(['loggedIn' => false, 'error' => 'Database error']);
    }
} else {
    // User is not logged in.
    echo json_encode(['loggedIn' => false]);
}
?>
