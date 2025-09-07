<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to like a video.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['video_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$video_id = intval($_POST['video_id']);

// Check if the user has already liked the video
$stmt = $conn->prepare("SELECT id FROM video_likes WHERE user_id = ? AND video_id = ?");
$stmt->bind_param("ii", $user_id, $video_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User has liked it, so unlike it
    $like = $result->fetch_assoc();
    $like_id = $like['id'];
    
    $delete_stmt = $conn->prepare("DELETE FROM video_likes WHERE id = ?");
    $delete_stmt->bind_param("i", $like_id);
    $delete_stmt->execute();

    $conn->query("UPDATE videos SET likes = likes - 1 WHERE id = $video_id");
    $action = 'unliked';

} else {
    // User has not liked it, so like it
    $insert_stmt = $conn->prepare("INSERT INTO video_likes (user_id, video_id) VALUES (?, ?)");
    $insert_stmt->bind_param("ii", $user_id, $video_id);
    $insert_stmt->execute();

    $conn->query("UPDATE videos SET likes = likes + 1 WHERE id = $video_id");
    $action = 'liked';
}

// Get the new like count
$count_result = $conn->query("SELECT likes FROM videos WHERE id = $video_id");
$new_count = $count_result->fetch_assoc()['likes'];

echo json_encode(['success' => true, 'action' => $action, 'like_count' => $new_count]);

$stmt->close();
$conn->close();
?>
