<?php
// Set content type to JSON and start the session.
header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db_connect.php';

// --- Input Validation ---
$videoId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
if (!$videoId) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid or missing video ID.']);
    exit;
}

// --- Prepare Response Structure ---
$response = [
    'success' => false,
    'is_logged_in' => false,
    'current_user' => null,
    'user_role' => null,
    'video' => null,
    'comments' => [],
    'related_videos' => []
];

// --- Check User Session ---
$userId = null;
if (isset($_SESSION['user_id'])) {
    $response['is_logged_in'] = true;
    $userId = $_SESSION['user_id'];
    $response['user_role'] = $_SESSION['role'];
    $response['current_user'] = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username']
    ];
}

try {
    // --- 1. Fetch Main Video Details ---
    $sql_video = "SELECT v.id, v.title, v.description, v.file_path, v.uploaded_at, v.views, v.likes, u.username as uploader_name, v.category
                  FROM videos v 
                  JOIN users u ON v.user_id = u.id 
                  WHERE v.id = ?";
    $stmt_video = $conn->prepare($sql_video);
    $stmt_video->bind_param("i", $videoId);
    $stmt_video->execute();
    $videoResult = $stmt_video->get_result();
    $video = $videoResult->fetch_assoc();
    $stmt_video->close();

    if (!$video) {
        http_response_code(404); // Not Found
        throw new Exception('Video not found.');
    }
    
    // --- 2. Check if the current user has liked this video ---
    $video['user_has_liked'] = false;
    if ($userId) {
        $sql_like = "SELECT 1 FROM video_likes WHERE video_id = ? AND user_id = ?";
        $stmt_like = $conn->prepare($sql_like);
        $stmt_like->bind_param("ii", $videoId, $userId);
        $stmt_like->execute();
        $stmt_like->store_result();
        if ($stmt_like->num_rows > 0) {
            $video['user_has_liked'] = true;
        }
        $stmt_like->close();
    }
    
    // --- 3. Fetch Comments ---
    $sql_comments = "SELECT c.id, c.comment, c.created_at, u.username 
                     FROM video_comments c 
                     JOIN users u ON c.user_id = u.id 
                     WHERE c.video_id = ? 
                     ORDER BY c.created_at DESC";
    $stmt_comments = $conn->prepare($sql_comments);
    $stmt_comments->bind_param("i", $videoId);
    $stmt_comments->execute();
    $commentsResult = $stmt_comments->get_result();
    while ($row = $commentsResult->fetch_assoc()) {
        $response['comments'][] = $row;
    }
    $stmt_comments->close();
    
    // Add comment count to the main video object
    $video['comment_count'] = count($response['comments']);
    $response['video'] = $video;

    // --- 4. Fetch Related Videos (simple logic: same category, not the same video) ---
    $sql_related = "SELECT v.id, v.title, v.thumbnail_path, u.username as uploader_name
                    FROM videos v
                    JOIN users u ON v.user_id = u.id
                    WHERE v.category = ? AND v.id != ?
                    ORDER BY v.uploaded_at DESC
                    LIMIT 4";
    $stmt_related = $conn->prepare($sql_related);
    $stmt_related->bind_param("si", $video['category'], $videoId);
    $stmt_related->execute();
    $relatedResult = $stmt_related->get_result();
    while ($row = $relatedResult->fetch_assoc()) {
        $response['related_videos'][] = $row;
    }
    $stmt_related->close();

    // --- Finalize Response ---
    $response['success'] = true;
    http_response_code(200);
    echo json_encode($response);

} catch (Throwable $e) {
    // Log the error for debugging, but don't show details to the user.
    error_log("Get Video Details Error: " . $e->getMessage());
    if (http_response_code() == 200) { // If no specific error code was set
        http_response_code(500); // Internal Server Error
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
