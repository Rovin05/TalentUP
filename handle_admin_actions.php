<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// --- AUTHENTICATION ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication failed.']);
    exit();
}

$action = $_POST['action'] ?? '';
$admin_id = $_SESSION['user_id'];

// --- ACTION ROUTING ---
switch ($action) {
    case 'change_role':
        handleChangeRole();
        break;
    case 'delete_user':
        handleDeleteUser();
        break;
    case 'add_judge':
        handleAddJudge();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

// --- FUNCTIONS ---

function handleAddJudge() {
    global $conn;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        return;
    }
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
        return;
    }

    // 2. Check for existing username or email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
        $stmt->close();
        return;
    }
    $stmt->close();

    // 3. Hash password and insert new judge
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'judge';
    // Assuming 'age_group' has a default or is nullable. If not, you might need to add it here.
    // For this example, we'll assume the DB defaults handle it.
    $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("ssss", $username, $email, $hashed_password, $role);
    
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Judge account created successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create judge account.']);
    }
    $stmt_insert->close();
    $conn->close();
}

function handleChangeRole() {
    global $conn, $admin_id;
    $user_id = intval($_POST['user_id'] ?? 0);
    $new_role = $_POST['new_role'] ?? '';
    $allowed_roles = ['user', 'judge', 'admin'];

    if (empty($user_id) || !in_array($new_role, $allowed_roles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
        return;
    }
    // Prevent admin from changing their own role via this form
    if ($user_id === $admin_id) {
        echo json_encode(['success' => false, 'message' => 'Cannot change your own role.']);
        return;
    }

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
    $stmt->close();
    $conn->close();
}

function handleDeleteUser() {
    global $conn, $admin_id;
    $user_id = intval($_POST['user_id'] ?? 0);

    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID not provided.']);
        return;
    }
    if ($user_id === $admin_id) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account.']);
        return;
    }

    // This is a complex operation, use a transaction
    $conn->begin_transaction();
    try {
        // 1. Get all videos by user to delete files
        $stmt_videos = $conn->prepare("SELECT id, file_path, thumbnail_path FROM videos WHERE user_id = ?");
        $stmt_videos->bind_param("i", $user_id);
        $stmt_videos->execute();
        $videos = $stmt_videos->get_result()->fetch_all(MYSQLI_ASSOC);

        // 2. Delete files and all related DB entries
        foreach ($videos as $video) {
            if (file_exists($video['file_path'])) unlink($video['file_path']);
            if (file_exists($video['thumbnail_path'])) unlink($video['thumbnail_path']);
        }
        
        // Use DELETE CASCADE in the database for simplicity, or delete from each table manually.
        // For this example, we'll assume foreign key constraints with ON DELETE CASCADE are set for:
        // videos(user_id), video_likes(user_id), video_comments(user_id), judge_votes(user_id).
        
        // 3. Delete the user
        $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete->bind_param("i", $user_id);
        $stmt_delete->execute();

        if ($stmt_delete->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("User could not be deleted from the database.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
    $conn->close();
}
?>

