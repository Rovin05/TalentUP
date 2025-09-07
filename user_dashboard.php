<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

// Fetch user's videos with all related counts
$sql = "SELECT 
            v.id, v.title, v.thumbnail_path, v.views, v.likes, v.uploaded_at,
            (SELECT COUNT(*) FROM video_comments WHERE video_id = v.id) as comment_count,
            (SELECT AVG(score) FROM judge_votes WHERE video_id = v.id) as avg_score
        FROM videos v
        WHERE v.user_id = ? 
        ORDER BY v.uploaded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total stats
$total_views = array_sum(array_column($user_videos, 'views'));
$total_likes = array_sum(array_column($user_videos, 'likes'));

$stmt->close();
$conn->close();

include 'header.php';
?>
<title>My Dashboard - TalentUp SriLanka</title>

<main class="flex-grow container mx-auto px-6 py-12">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p class="text-gray-400 text-lg mt-1">Manage your content and track your performance.</p>
            </div>
            <a href="profile.php" class="mt-4 md:mt-0 inline-block bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition">Manage Profile</a>
        </div>

        <?php if ($success_message): ?>
            <div id="success-alert" class="bg-green-500 text-white p-4 rounded-lg mb-8 flex justify-between items-center shadow-lg">
                <span><?php echo htmlspecialchars($success_message); ?></span>
                <button onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-gray-800 p-6 rounded-lg shadow-xl text-center"><p class="text-3xl font-bold"><?php echo count($user_videos); ?></p><p class="text-gray-400">Total Videos</p></div>
            <div class="bg-gray-800 p-6 rounded-lg shadow-xl text-center"><p class="text-3xl font-bold"><?php echo number_format($total_views); ?></p><p class="text-gray-400">Total Views</p></div>
            <div class="bg-gray-800 p-6 rounded-lg shadow-xl text-center"><p class="text-3xl font-bold"><?php echo number_format($total_likes); ?></p><p class="text-gray-400">Total Likes</p></div>
        </div>

        <div class="bg-gray-800/50 p-8 rounded-lg shadow-xl">
             <div class="flex justify-between items-center border-b border-gray-700 pb-6 mb-6">
                <h2 class="text-2xl font-semibold">My Videos</h2>
                <a href="upload.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-5 rounded-lg transition">+ Upload</a>
            </div>
            <?php if (empty($user_videos)): ?>
                <p class="text-center text-gray-400 py-10">You haven't uploaded any videos yet.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($user_videos as $video): ?>
                    <div id="video-row-<?php echo $video['id']; ?>" class="bg-gray-800 p-4 rounded-lg flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-4">
                        <a href="video_player.php?id=<?php echo $video['id']; ?>" class="flex-shrink-0 w-full md:w-32">
                             <img src="<?php echo htmlspecialchars($video['thumbnail_path']); ?>" class="rounded-md w-full h-20 object-cover">
                        </a>
                        <div class="flex-grow text-center md:text-left"><p class="font-bold text-lg"><?php echo htmlspecialchars($video['title']); ?></p></div>
                        <div class="flex-shrink-0 grid grid-cols-2 sm:grid-cols-4 gap-4 text-center w-full md:w-auto">
                            <div class="px-2"><p class="font-bold"><?php echo number_format($video['views']); ?></p><p class="text-xs text-gray-400">Views</p></div>
                            <div class="px-2"><p class="font-bold"><?php echo number_format($video['likes']); ?></p><p class="text-xs text-gray-400">Likes</p></div>
                            <div class="px-2"><p class="font-bold"><?php echo number_format($video['comment_count']); ?></p><p class="text-xs text-gray-400">Comments</p></div>
                             <div class="px-2"><p class="font-bold"><?php echo $video['avg_score'] ? number_format($video['avg_score'], 1) : 'N/A'; ?></p><p class="text-xs text-gray-400">Avg. Score</p></div>
                        </div>
                        <div class="flex-shrink-0 flex space-x-2">
                             <a href="edit_video.php?id=<?php echo $video['id']; ?>" class="bg-gray-600 hover:bg-gray-500 text-white py-2 px-3 rounded-lg text-sm">Edit</a>
                             <button onclick="confirmDelete(<?php echo $video['id']; ?>)" class="bg-red-600 hover:bg-red-500 text-white py-2 px-3 rounded-lg text-sm">Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
    <div class="bg-gray-800 rounded-lg p-8 w-full max-w-md mx-4">
        <h3 class="text-2xl font-bold mb-4">Are you sure?</h3>
        <p class="text-gray-400 mb-6">This action cannot be undone. All data associated with this video will be permanently deleted.</p>
        <div class="flex justify-end space-x-4">
            <button id="cancel-delete" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg">Cancel</button>
            <button id="confirm-delete-btn" class="bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-4 rounded-lg">Delete</button>
        </div>
    </div>
</div>

<script>
function confirmDelete(videoId) {
    const modal = document.getElementById('delete-modal');
    modal.classList.remove('hidden');
    
    document.getElementById('cancel-delete').onclick = () => modal.classList.add('hidden');
    document.getElementById('confirm-delete-btn').onclick = () => {
        deleteVideo(videoId);
        modal.classList.add('hidden');
    };
}

function deleteVideo(videoId) {
    const formData = new FormData();
    formData.append('video_id', videoId);

    fetch('delete_video.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const videoRow = document.getElementById('video-row-' + videoId);
            if (videoRow) {
                videoRow.style.transition = 'opacity 0.5s';
                videoRow.style.opacity = '0';
                setTimeout(() => videoRow.remove(), 500);
            }
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

</body>
</html>