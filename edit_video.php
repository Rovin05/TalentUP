<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: user_dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$video_id = intval($_GET['id']);
$errors = [];

// Fetch video details to ensure the user owns it
$stmt = $conn->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $video_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // Video not found or doesn't belong to the user
    header('Location: user_dashboard.php');
    exit();
}
$video = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $thumbnail_path = $video['thumbnail_path']; // Keep old thumbnail by default

    if (empty($title) || empty($description) || empty($category)) {
        $errors[] = "Title, description, and category cannot be empty.";
    }

    // Handle new thumbnail upload
    if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === 0) {
        $thumbnail = $_FILES['thumbnail_file'];
        $allowed_thumb_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($thumbnail['type'], $allowed_thumb_types)) {
            $thumb_ext = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
            $thumb_filename = $user_id . '_' . time() . '_thumb.' . $thumb_ext;
            $new_thumbnail_path = 'uploads/thumbnails/' . $thumb_filename;

            if (move_uploaded_file($thumbnail['tmp_name'], $new_thumbnail_path)) {
                // Delete old thumbnail if it exists and is not a placeholder
                if (!empty($thumbnail_path) && file_exists($thumbnail_path)) {
                    unlink($thumbnail_path);
                }
                $thumbnail_path = $new_thumbnail_path; // Set the new path for DB update
            } else {
                $errors[] = "Failed to upload new thumbnail.";
            }
        } else {
            $errors[] = "Invalid thumbnail file type.";
        }
    }

    if (empty($errors)) {
        $update_stmt = $conn->prepare("UPDATE videos SET title = ?, description = ?, category = ?, thumbnail_path = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $title, $description, $category, $thumbnail_path, $video_id);
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Video updated successfully!";
            header('Location: user_dashboard.php');
            exit();
        } else {
            $errors[] = "Database error: Could not update video.";
        }
    }
}

include 'header.php';
?>
<title>Edit Video - TalentUp SriLanka</title>
<main class="flex-grow container mx-auto px-6 py-12">
    <div class="max-w-3xl mx-auto bg-gray-800 p-8 md:p-10 rounded-lg shadow-xl">
        <h1 class="text-3xl font-bold mb-6">Edit Your Video</h1>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form action="edit_video.php?id=<?php echo $video_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="title" class="block text-gray-300 mb-2">Title</label>
                <input type="text" name="title" id="title" class="w-full bg-gray-700 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($video['title']); ?>" required>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-300 mb-2">Description</label>
                <textarea name="description" id="description" rows="5" class="w-full bg-gray-700 rounded-lg px-4 py-2" required><?php echo htmlspecialchars($video['description']); ?></textarea>
            </div>
            <div class="mb-6">
                <label for="category" class="block text-gray-300 mb-2">Category</label>
                <select name="category" id="category" class="w-full bg-gray-700 rounded-lg px-4 py-2" required>
                    <?php $categories = ['Singing', 'Dancing', 'Magic', 'Comedy', 'Instrumental', 'Other']; ?>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php if ($video['category'] == $cat) echo 'selected'; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-gray-300 mb-2">Current Thumbnail</label>
                <img src="<?php echo htmlspecialchars($video['thumbnail_path']); ?>" alt="Current Thumbnail" class="rounded-lg w-1/2">
            </div>
             <div class="mb-6">
                <label for="thumbnail_file" class="block text-gray-300 mb-2">Upload New Thumbnail (Optional)</label>
                <input type="file" name="thumbnail_file" id="thumbnail_file" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600">
            </div>
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg">Save Changes</button>
        </form>
    </div>
</main>
</body>
</html>
