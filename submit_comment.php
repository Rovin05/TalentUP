<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['video_id']) || empty($data['comment'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$video_id = intval($data['video_id']);
$comment_text = trim($data['comment']);
$user_id = $_SESSION['user_id'];

// DB connection
$conn = new mysqli('localhost', 'root', '', 'talentup_db');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// Very basic sentiment analysis placeholder
function get_sentiment($text) {
    $text = strtolower($text);
    if (strpos($text, 'good') !== false || strpos($text, 'great') !== false) return 'positive';
    if (strpos($text, 'bad') !== false || strpos($text, 'terrible') !== false) return 'negative';
    return 'neutral';
}

$sentiment = get_sentiment($comment_text);

// Insert comment into DB (assuming video_comments table with sentiment column)
$stmt = $conn->prepare("INSERT INTO video_comments (video_id, user_id, comment, sentiment, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iiss", $video_id, $user_id, $comment_text, $sentiment);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to save comment']);
    exit;
}

// Fetch username and role for response
$user_stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'comment' => [
        'username' => $user_result['username'],
        'role' => $user_result['role'],
        'sentiment' => $sentiment,
        'created_at' => date('c'),
        'comment' => htmlspecialchars($comment_text, ENT_QUOTES),
    ]
]);
$stmt->close();
$conn->close();
?>
