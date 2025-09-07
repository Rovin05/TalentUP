<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// ✅ 1. Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to comment.']);
    exit();
}

// ✅ 2. Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty(trim($_POST['comment'])) || !isset($_POST['video_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$video_id = intval($_POST['video_id']);
$comment_text = trim($_POST['comment']);

// ✅ 3. Sentiment prediction via Flask API
function predict_sentiment($text) {
    $url = 'http://localhost:5001/';
    $data = http_build_query(['text' => $text]);

    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded",
            'method' => 'POST',
            'content' => $data,
            'timeout' => 5
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result !== false) {
        // Check if response is valid JSON
        $json = json_decode($result, true);
        if ($json && isset($json['result'])) {
            return strtolower($json['result']);
        }
    }

    // Fallback if API fails
    return 'neutral';
}

$sentiment = predict_sentiment($comment_text);

// ✅ 4. Insert comment into DB
$stmt = $conn->prepare("
    INSERT INTO video_comments (video_id, user_id, comment, sentiment)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiss", $video_id, $user_id, $comment_text, $sentiment);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'comment' => [
            'username' => htmlspecialchars($username),
            'comment' => htmlspecialchars($comment_text),
            'sentiment' => $sentiment,
            'created_at' => date("M d, Y, g:i A"),
            'role' => $role
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to post comment.']);
}

$stmt->close();
$conn->close();
?>
