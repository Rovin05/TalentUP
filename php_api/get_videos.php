<?php
// This script fetches a list of all videos for the public video_list.php page.

header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

try {
    // Use the session to get the current user's ID. Default to 0 if not logged in.
    $current_user_id = $_SESSION['user_id'] ?? 0;

    // This single, efficient query fetches all necessary data at once.
    // It uses subqueries to calculate the like count and check if the current user has liked the video.
    $sql = "SELECT 
                v.id, 
                v.title, 
                v.description, 
                v.thumbnail_path, 
                v.views, 
                v.uploaded_at, 
                u.username AS uploader_name,
                (SELECT COUNT(*) FROM video_likes WHERE video_id = v.id) AS likes,
                (SELECT COUNT(*) FROM video_likes WHERE video_id = v.id AND user_id = ?) AS user_has_liked
            FROM videos v
            JOIN users u ON v.user_id = u.id
            ORDER BY v.uploaded_at DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    // Bind the current user's ID to the placeholder in the subquery.
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        // The user_has_liked column will be 1 if liked, 0 if not. Convert this to a boolean for JavaScript.
        $row['user_has_liked'] = (bool)$row['user_has_liked'];
        $videos[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'videos' => $videos]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Get videos error: " . $e->getMessage()); // Log the specific error for debugging
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching videos.'
    ]);
}

$conn->close();
?>

