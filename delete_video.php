<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['video_id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$video_id = intval($_POST['video_id']);

$conn->begin_transaction();

try {
    // Fetch video details to verify ownership and get file paths
    $stmt = $conn->prepare("SELECT user_id, file_path, thumbnail_path FROM videos WHERE id = ?");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Video not found.");
    }

    $video = $result->fetch_assoc();

    if ($video['user_id'] !== $user_id) {
        throw new Exception("You do not have permission to delete this video.");
    }

    // Delete the video record from the database
    $delete_stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
    $delete_stmt->bind_param("i", $video_id);
    $delete_stmt->execute();

    if ($delete_stmt->affected_rows > 0) {
        // Delete the actual files from the server
        if (!empty($video['file_path']) && file_exists($video['file_path'])) {
            unlink($video['file_path']);
        }
        if (!empty($video['thumbnail_path']) && file_exists($video['thumbnail_path'])) {
            unlink($video['thumbnail_path']);
        }
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to delete video record from database.");
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
