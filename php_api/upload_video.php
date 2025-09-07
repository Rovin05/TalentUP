<?php
// --- Enhanced Session Start for AJAX/Fetch Requests ---
// This robust session handling is crucial for file uploads.
// It ensures the session cookie parameters are correctly set before starting.
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => '', // Your domain here in production
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// --- Centralized Logging ---
// A simple function to log messages for easier debugging.
function write_log($message) {
    $log_path = __DIR__ . '/../logs/upload_log.txt';
    // Ensure the logs directory exists
    if (!file_exists(dirname($log_path))) {
        mkdir(dirname($log_path), 0777, true);
    }
    // Append message to the log file with a timestamp
    file_put_contents($log_path, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// Start a new log entry for this attempt
write_log("--- New Upload Attempt ---");

// --- Central Database Connection ---
// Include the database connection file AFTER starting the session.
require_once 'db_connect.php';
write_log("db_connect.php included successfully.");


// --- Security Check ---
// Verify that the user is logged in by checking the session.
if (!isset($_SESSION['user_id'])) {
    write_log("ERROR: Session check failed: user_id not set.");
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
    exit;
}

write_log("Session check passed for user_id: " . $_SESSION['user_id']);


// --- Main Upload Logic ---
try {
    // Check if files were uploaded
    if (!isset($_FILES['videoFile']) || !isset($_FILES['thumbnailFile'])) {
        throw new Exception('Video file or thumbnail file not provided.');
    }

    $videoFile = $_FILES['videoFile'];
    $thumbnailFile = $_FILES['thumbnailFile'];
    $userId = $_SESSION['user_id'];
    
    // Extract form data
    $title = $_POST['videoTitle'] ?? 'Untitled Video';
    $description = $_POST['videoDescription'] ?? '';
    $category = $_POST['videoCategory'] ?? 'Other';
    
    write_log("Received upload for user ID {$userId}: '{$title}'");

    // --- File Validation ---
    // Video File Validation
    if ($videoFile['error'] !== UPLOAD_ERR_OK) throw new Exception('Error uploading video file. Code: ' . $videoFile['error']);
    $video_mime_type = mime_content_type($videoFile['tmp_name']);
    $allowed_video_types = ['video/mp4', 'video/webm', 'video/quicktime'];
    if (!in_array($video_mime_type, $allowed_video_types)) throw new Exception('Invalid video file type.');
    
    // Thumbnail File Validation
    if ($thumbnailFile['error'] !== UPLOAD_ERR_OK) throw new Exception('Error uploading thumbnail file. Code: ' . $thumbnailFile['error']);
    $thumb_mime_type = mime_content_type($thumbnailFile['tmp_name']);
    $allowed_thumb_types = ['image/jpeg', 'image/png'];
    if (!in_array($thumb_mime_type, $allowed_thumb_types)) throw new Exception('Invalid thumbnail file type.');

    write_log("File validation passed.");

    // --- File Handling ---
    $upload_dir = __DIR__ . '/../uploads/';
    $thumb_dir = $upload_dir . 'thumbnails/';

    // Create directories if they don't exist
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);

    // Generate unique filenames to prevent overwrites
    $video_extension = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
    $video_filename = uniqid('video_', true) . '.' . $video_extension;
    $video_path = $upload_dir . $video_filename;
    
    $thumb_extension = pathinfo($thumbnailFile['name'], PATHINFO_EXTENSION);
    $thumb_filename = uniqid('thumb_', true) . '.' . $thumb_extension;
    $thumb_path = $thumb_dir . $thumb_filename;

    // Move uploaded files to their new location
    if (!move_uploaded_file($videoFile['tmp_name'], $video_path)) throw new Exception('Failed to save video file.');
    if (!move_uploaded_file($thumbnailFile['tmp_name'], $thumb_path)) throw new Exception('Failed to save thumbnail file.');
    
    write_log("Files saved to server. Video: {$video_path}, Thumbnail: {$thumb_path}");


    // --- Database Insertion ---
    // Store relative paths for portability
    $db_video_path = 'uploads/' . $video_filename;
    $db_thumb_path = 'uploads/thumbnails/' . $thumb_filename;

    $stmt = $conn->prepare("INSERT INTO videos (user_id, title, description, category, file_path, thumbnail_path) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt === false) throw new Exception("Database prepare failed: " . $conn->error);
        
    $stmt->bind_param("isssss", $userId, $title, $description, $category, $db_video_path, $db_thumb_path);
    
    if ($stmt->execute()) {
        write_log("Database insert successful for video ID: " . $stmt->insert_id);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Video uploaded successfully!']);
    } else {
        throw new Exception("Database execute failed: " . $stmt->error);
    }
    $stmt->close();
    
} catch (Exception $e) {
    // --- Error Handling ---
    $error_message = $e->getMessage();
    write_log("ERROR: " . $error_message);
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $error_message]);
}

$conn->close();
?>

