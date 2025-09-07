<?php
// Enhanced error reporting for debugging purposes.
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure a user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

// Wrap the entire logic in a try-catch block for robust error handling.
try {
    require 'db_connect.php';

    $userId = $_SESSION['user_id'];
    $response = [
        'success' => false,
        'stats' => [],
        'videos' => []
    ];

    // --- 1. Fetch Aggregated Video Statistics (Views, Count) ---
    $sql_stats = "SELECT 
                    COUNT(v.id) as total_videos, 
                    COALESCE(SUM(v.views), 0) as total_views
                  FROM videos v WHERE v.user_id = ?";
    $stmt_stats = $conn->prepare($sql_stats);
    if ($stmt_stats === false) {
        throw new Exception("Prepare failed (stats): " . $conn->error);
    }
    $stmt_stats->bind_param("i", $userId);
    $stmt_stats->execute();
    $stats_result = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();
    
    // --- 2. Fetch Total Like Count from the 'video_likes' table ---
    $sql_likes = "SELECT COUNT(vl.id) as total_likes 
                  FROM video_likes vl 
                  JOIN videos v ON vl.video_id = v.id 
                  WHERE v.user_id = ?";
    $stmt_likes = $conn->prepare($sql_likes);
    if ($stmt_likes === false) {
        throw new Exception("Prepare failed (likes): " . $conn->error);
    }
    $stmt_likes->bind_param("i", $userId);
    $stmt_likes->execute();
    $likes_result = $stmt_likes->get_result()->fetch_assoc();
    $stmt_likes->close();

    // --- 3. Fetch Total Comment Count from the 'video_comments' table ---
    $sql_comments = "SELECT COUNT(vc.id) as total_comments 
                     FROM video_comments vc 
                     JOIN videos v ON vc.video_id = v.id 
                     WHERE v.user_id = ?";
    $stmt_comments = $conn->prepare($sql_comments);
    if ($stmt_comments === false) {
        throw new Exception("Prepare failed (comments): " . $conn->error);
    }
    $stmt_comments->bind_param("i", $userId);
    $stmt_comments->execute();
    $comments_result = $stmt_comments->get_result()->fetch_assoc();
    $stmt_comments->close();

    // Combine all stats into one object
    $response['stats'] = [
        'total_videos' => (int) $stats_result['total_videos'],
        'total_views' => (int) $stats_result['total_views'],
        'total_likes' => (int) $likes_result['total_likes'],
        'total_comments' => (int) $comments_result['total_comments']
    ];

    // --- 4. Fetch Recent Videos with their individual like and comment counts using subqueries ---
    $sql_videos = "SELECT 
                       v.id, v.title, v.thumbnail_path, v.views, v.uploaded_at,
                       (SELECT COUNT(id) FROM video_likes WHERE video_id = v.id) as likes,
                       (SELECT COUNT(id) FROM video_comments WHERE video_id = v.id) as comment_count
                   FROM videos v
                   WHERE v.user_id = ?
                   ORDER BY v.uploaded_at DESC
                   LIMIT 5";
    $stmt_videos = $conn->prepare($sql_videos);
    if ($stmt_videos === false) {
        throw new Exception("Prepare failed (videos): " . $conn->error);
    }
    $stmt_videos->bind_param("i", $userId);
    $stmt_videos->execute();
    $videos_result = $stmt_videos->get_result();
    
    $videos = [];
    while ($row = $videos_result->fetch_assoc()) {
        $row['title'] = htmlspecialchars($row['title']);
        $row['views'] = (int) $row['views'];
        $row['likes'] = (int) $row['likes'];
        $row['comment_count'] = (int) $row['comment_count'];
        $videos[] = $row;
    }
    $stmt_videos->close();
    
    $response['videos'] = $videos;
    $response['success'] = true;
    
    http_response_code(200);
    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'A server error occurred: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>

