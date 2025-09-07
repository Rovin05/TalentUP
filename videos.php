<?php
include 'header.php';
require 'db_connect.php';

// Fetch all videos along with the uploader's username
// We use a LEFT JOIN to get the username from the users table
$sql = "SELECT v.id, v.title, v.views, v.likes, v.thumbnail_path, v.uploaded_at, u.username 
        FROM videos v 
        JOIN users u ON v.user_id = u.id 
        ORDER BY v.uploaded_at DESC";

$result = $conn->query($sql);

$videos = [];
if ($result && $result->num_rows > 0) {
    $videos = $result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>
<title>Watch Videos - TalentUp SriLanka</title>

<main class="flex-grow container mx-auto px-6 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold">Talent Showcase</h1>
        <p class="text-lg text-gray-400 mt-2">Browse the amazing talents from all over Sri Lanka!</p>
    </div>

    <?php if (empty($videos)): ?>
        <div class="text-center bg-gray-800 p-12 rounded-lg">
            <h2 class="text-2xl font-semibold text-gray-300">No Videos Yet!</h2>
            <p class="text-gray-400 mt-2">Be the first to upload a talent video and get featured here.</p>
            <a href="upload.php" class="mt-6 inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                Upload Your Talent
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach ($videos as $video): ?>
                <a href="video_player.php?id=<?php echo $video['id']; ?>" class="block group">
                    <div class="bg-gray-800 rounded-lg overflow-hidden shadow-lg transform group-hover:-translate-y-2 transition-transform duration-300 h-full flex flex-col">
                        <div class="relative">
                            <!-- Use the actual thumbnail_path, with a placeholder as a fallback -->
                            <img src="<?php echo !empty($video['thumbnail_path']) ? htmlspecialchars($video['thumbnail_path']) : 'https://placehold.co/600x400/003049/FFFFFF?text=TalentUp'; ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="w-full h-48 object-cover">
                             <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                            </div>
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="text-lg font-semibold mb-1 text-white truncate"><?php echo htmlspecialchars($video['title']); ?></h3>
                            <p class="text-gray-400 text-sm mb-2">by <?php echo htmlspecialchars($video['username']); ?></p>
                            <div class="flex-grow"></div>
                            <div class="flex justify-between items-center text-sm text-gray-400 mt-2">
                                <span><?php echo number_format($video['views']); ?> Views</span>
                                <span><?php echo number_format($video['likes']); ?> Likes</span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>

