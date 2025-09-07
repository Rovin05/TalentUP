<?php
// This script handles a user liking or unliking a video.

// --- Initialization ---
header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

// --- Security Check ---
// Ensure a user is logged in to like a video.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'You must be logged in to like a video.']);
    exit;
}

// --- Input Processing ---
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
$video_id = filter_var($data['video_id'] ?? null, FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$video_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid video ID.']);
    exit;
}

try {
    $conn->begin_transaction();

    // --- Check if the user has already liked this video ---
    $check_sql = "SELECT id FROM video_likes WHERE user_id = ? AND video_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $video_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $existing_like = $result->fetch_assoc();
    $check_stmt->close();

    $user_has_liked = false;

    if ($existing_like) {
        // --- Unlike the video ---
        $delete_sql = "DELETE FROM video_likes WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $existing_like['id']);
        $delete_stmt->execute();
        $delete_stmt->close();
        $user_has_liked = false;
    } else {
        // --- Like the video ---
        $insert_sql = "INSERT INTO video_likes (user_id, video_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $user_id, $video_id);
        $insert_stmt->execute();
        $insert_stmt->close();
        $user_has_liked = true;
    }

    // --- Get the new total like count ---
    $count_sql = "SELECT COUNT(*) as new_like_count FROM video_likes WHERE video_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $video_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $new_like_count = $count_result->fetch_assoc()['new_like_count'];
    $count_stmt->close();
    
    $conn->commit();

    // --- Send Response ---
    echo json_encode([
        'success' => true,
        'user_has_liked' => $user_has_liked,
        'new_like_count' => $new_like_count
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request.'
    ]);
}

$conn->close();
?>
