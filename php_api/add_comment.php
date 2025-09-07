<?php
header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'You must be logged in to comment.']);
    exit;
}

require 'db_connect.php';

// 2. Data Intake and Validation
$input = json_decode(file_get_contents('php://input'), true);

$videoId = isset($input['video_id']) ? filter_var($input['video_id'], FILTER_VALIDATE_INT) : null;
$commentText = isset($input['comment']) ? trim($input['comment']) : '';
$userId = $_SESSION['user_id'];

if (!$videoId || empty($commentText)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid input provided.']);
    exit;
}

// Sanitize comment to prevent XSS
$commentText = htmlspecialchars($commentText, ENT_QUOTES, 'UTF-8');

try {
    // 3. Database Insertion
    $sql = "INSERT INTO video_comments (video_id, user_id, comment) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare statement failed: " . $conn->error);
    }
    
    $stmt->bind_param("iis", $videoId, $userId, $commentText);
    
    if ($stmt->execute()) {
        // 4. Success Response
        http_response_code(201); // Created
        echo json_encode(['success' => true, 'message' => 'Comment posted successfully.']);
    } else {
        throw new Exception("Failed to execute statement.");
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // 5. Error Handling
    error_log("Add Comment Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'A server error occurred while posting your comment.']);
}

$conn->close();
?>
