<?php
// This script safely increments the view count for a video.
// It uses a session to prevent a single user from incrementing the count multiple times
// by simply refreshing the page.

// --- Initialization ---
header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

// --- Input Processing ---
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
$video_id = filter_var($data['video_id'] ?? null, FILTER_VALIDATE_INT);

if (!$video_id) {
    // No need to send an error, just exit gracefully.
    exit;
}

try {
    // --- Session Check to Prevent View Spamming ---
    // Initialize the viewed videos array in the session if it doesn't exist.
    if (!isset($_SESSION['viewed_videos'])) {
        $_SESSION['viewed_videos'] = [];
    }

    // Check if this video ID has already been logged in this session.
    if (!in_array($video_id, $_SESSION['viewed_videos'])) {
        
        // --- Database Update ---
        // The user hasn't viewed this video in this session, so increment the count.
        $sql = "UPDATE videos SET views = views + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        $stmt->close();

        // --- Record the View in the Session ---
        // Add the video ID to the session array to prevent future increments.
        $_SESSION['viewed_videos'][] = $video_id;

        echo json_encode(['success' => true, 'message' => 'View count updated.']);
    } else {
        // The user has already viewed this video in the current session.
        echo json_encode(['success' => true, 'message' => 'View already counted for this session.']);
    }

} catch (Exception $e) {
    // Log the error for debugging, but don't send a failure message to the client.
    // The view count failing should not interrupt the user experience.
    error_log("View count increment failed: " . $e->getMessage());
    // Silently fail.
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}

$conn->close();
?>
