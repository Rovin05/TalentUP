<?php
// Start the session and include database connection FIRST.
// All logic that might cause a redirect must happen before any HTML is output.
session_start();
require 'db_connect.php';

// Redirect user to login page if they are not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$title = '';
$description = '';
$category = '';

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);

    // --- Form Data Validation ---
    if (empty($title)) { $errors[] = "Video title is required."; }
    if (empty($description)) { $errors[] = "Video description is required."; }
    if (empty($category)) { $errors[] = "Please select a category."; }

    $video_file_path = '';
    $thumbnail_file_path = '';

    // --- Video File Upload Validation ---
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
        $video = $_FILES['video_file'];
        $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg', 'video/mov', 'video/avi'];
        $max_video_size = 100 * 1024 * 1024; // 100 MB

        if (!in_array($video['type'], $allowed_video_types)) {
            $errors[] = "Invalid video file type. Please upload MP4, WEBM, MOV, AVI, or OGG.";
        }
        if ($video['size'] > $max_video_size) {
            $errors[] = "Video file is too large. Maximum size is 100MB.";
        }
    } else {
        $errors[] = "A video file is required.";
    }

    // --- Thumbnail File Upload Validation ---
    if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] == 0) {
        $thumbnail = $_FILES['thumbnail_file'];
        $allowed_thumb_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_thumb_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($thumbnail['type'], $allowed_thumb_types)) {
            $errors[] = "Invalid thumbnail file type. Please upload JPG, PNG, WEBP or GIF.";
        }
        if ($thumbnail['size'] > $max_thumb_size) {
            $errors[] = "Thumbnail file is too large. Maximum size is 5MB.";
        }
    } else {
        $errors[] = "A thumbnail image is required.";
    }


    // --- Process and Save if No Errors ---
    if (empty($errors)) {
        // Handle Video File
        $video_ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
        $video_filename = $user_id . '_' . time() . '_' . uniqid() . '.' . $video_ext;
        $video_file_path = 'uploads/videos/' . $video_filename;

        // Handle Thumbnail File
        $thumb_ext = pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION);
        $thumb_filename = $user_id . '_' . time() . '_' . uniqid() . '_thumb.' . $thumb_ext;
        $thumbnail_file_path = 'uploads/thumbnails/' . $thumb_filename;

        // Move the files to their respective directories
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $video_file_path) && move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $thumbnail_file_path)) {
            // Files moved successfully, now insert into database
            $stmt = $conn->prepare("INSERT INTO videos (user_id, title, description, category, file_path, thumbnail_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $user_id, $title, $description, $category, $video_file_path, $thumbnail_file_path);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Your video has been uploaded successfully!";
                header('Location: user_dashboard.php');
                exit();
            } else {
                $errors[] = "Database error: Failed to save video information.";
                // Optional: Delete the uploaded files if DB insert fails
                unlink($video_file_path);
                unlink($thumbnail_file_path);
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to move one or more uploaded files. Check folder permissions.";
        }
    }
    $conn->close();
}

// Now that all the PHP logic is done, we can start outputting HTML.
include 'header.php';
?>
<title>Upload Video - TalentUp SriLanka</title>

<main class="flex-grow container mx-auto px-6 py-12">
    <div class="max-w-3xl mx-auto bg-gray-800 p-8 md:p-10 rounded-lg shadow-xl">
        <h1 class="text-3xl font-bold mb-6 text-center">Upload Your Talent</h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <div class="mb-6">
                <label for="title" class="block text-gray-300 mb-2 font-semibold">Video Title</label>
                <input type="text" id="title" name="title" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 'My Amazing Singing Performance'" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>

            <div class="mb-6">
                <label for="description" class="block text-gray-300 mb-2 font-semibold">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tell us about your performance, what inspired you, etc." required><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="mb-6">
                <label for="category" class="block text-gray-300 mb-2 font-semibold">Category</label>
                <select id="category" name="category" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="" disabled selected>-- Select a Category --</option>
                    <option value="Singing" <?php if ($category == 'Singing') echo 'selected'; ?>>Singing</option>
                    <option value="Dancing" <?php if ($category == 'Dancing') echo 'selected'; ?>>Dancing</option>
                    <option value="Magic" <?php if ($category == 'Magic') echo 'selected'; ?>>Magic</option>
                    <option value="Comedy" <?php if ($category == 'Comedy') echo 'selected'; ?>>Comedy</option>
                    <option value="Instrumental" <?php if ($category == 'Instrumental') echo 'selected'; ?>>Instrumental</option>
                    <option value="Other" <?php if ($category == 'Other') echo 'selected'; ?>>Other</option>
                </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                 <!-- Video Upload -->
                <div>
                    <label class="block text-gray-300 mb-2 font-semibold">Video File</label>
                    <div id="video-drop-zone" class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-500 px-6 py-10 transition-colors duration-300 h-full items-center">
                        <div id="video-upload-placeholder" class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <label for="video_file" class="relative cursor-pointer rounded-md font-semibold text-blue-400 hover:text-blue-500"><span>Upload Video</span><input id="video_file" name="video_file" type="file" class="sr-only" required accept="video/*"></label>
                            <p class="text-xs text-gray-500">MP4, WEBM, MOV up to 100MB</p>
                        </div>
                        <div id="video-preview-container" class="hidden w-full">
                            <video id="video-preview" class="rounded-lg max-h-48 w-full" controls></video>
                            <p id="video-file-name" class="text-center text-sm text-gray-400 mt-2"></p>
                        </div>
                    </div>
                </div>
                 <!-- Thumbnail Upload -->
                <div>
                    <label class="block text-gray-300 mb-2 font-semibold">Thumbnail Image</label>
                    <div id="thumb-drop-zone" class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-500 px-6 py-10 transition-colors duration-300 h-full items-center">
                        <div id="thumb-upload-placeholder" class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <label for="thumbnail_file" class="relative cursor-pointer rounded-md font-semibold text-blue-400 hover:text-blue-500"><span>Upload Image</span><input id="thumbnail_file" name="thumbnail_file" type="file" class="sr-only" required accept="image/*"></label>
                            <p class="text-xs text-gray-500">PNG, JPG, WEBP up to 5MB</p>
                        </div>
                         <div id="thumb-preview-container" class="hidden w-full">
                            <img id="thumb-preview" class="rounded-lg max-h-48 w-full object-contain" alt="Thumbnail preview"/>
                            <p id="thumb-file-name" class="text-center text-sm text-gray-400 mt-2"></p>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg transition duration-300 text-lg">Submit My Talent</button>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Generic File Preview Logic ---
    function setupFilePreview(dropZoneId, fileInputId, placeholderId, previewContainerId, previewElementId, fileNameId, isVideo) {
        const dropZone = document.getElementById(dropZoneId);
        const fileInput = document.getElementById(fileInputId);
        const placeholder = document.getElementById(placeholderId);
        const previewContainer = document.getElementById(previewContainerId);
        const previewElement = document.getElementById(previewElementId);
        const fileNameDisplay = document.getElementById(fileNameId);

        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
        dropZone.addEventListener('click', (e) => {
            if (e.target.tagName !== 'VIDEO' && e.target.tagName !== 'IMG') {
                fileInput.click();
            }
        });
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-blue-500'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-blue-500'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500');
            const files = e.dataTransfer.files;
            fileInput.files = files;
            handleFiles(files);
        });

        function handleFiles(files) {
            if (!files || files.length === 0) return;
            const file = files[0];
            const fileURL = URL.createObjectURL(file);
            
            placeholder.classList.add('hidden');
            previewContainer.classList.remove('hidden');
            previewElement.src = fileURL;
            fileNameDisplay.textContent = file.name;

            if (isVideo) {
                 previewElement.onload = () => URL.revokeObjectURL(previewElement.src); // Free up memory
            }
        }
    }

    // --- Setup for Video ---
    setupFilePreview('video-drop-zone', 'video_file', 'video-upload-placeholder', 'video-preview-container', 'video-preview', 'video-file-name', true);

    // --- Setup for Thumbnail ---
    setupFilePreview('thumb-drop-zone', 'thumbnail_file', 'thumb-upload-placeholder', 'thumb-preview-container', 'thumb-preview', 'thumb-file-name', false);
});
</script>

</body>
</html>

