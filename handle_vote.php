<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'judge') {
    echo json_encode(['success' => false, 'message' => 'Only judges are allowed to vote.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['video_id']) || !isset($_POST['score'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$judge_id = $_SESSION['user_id'];
$video_id = intval($_POST['video_id']);
$score = intval($_POST['score']);

if ($score < 0 || $score > 100) {
    echo json_encode(['success' => false, 'message' => 'Score must be between 0 and 100.']);
    exit();
}

// Use INSERT ... ON DUPLICATE KEY UPDATE to either add a new vote or update an existing one
$stmt = $conn->prepare(
    "INSERT INTO judge_votes (user_id, video_id, score) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE score = VALUES(score)"
);
$stmt->bind_param("iii", $judge_id, $video_id, $score);

if ($stmt->execute()) {
    // Calculate the new average score for the video
    $avg_stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM judge_votes WHERE video_id = ?");
    $avg_stmt->bind_param("i", $video_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result()->fetch_assoc();
    $new_average = round($avg_result['avg_score'], 1);

    echo json_encode(['success' => true, 'message' => 'Your vote has been recorded!', 'new_average' => $new_average]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: Could not record your vote.']);
}

$stmt->close();
$conn->close();
?>
