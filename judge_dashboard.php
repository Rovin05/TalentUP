<?php
session_start();
require 'db_connect.php';

// Authenticate: Ensure the user is logged in and is a judge.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'judge') {
    header('Location: login.php');
    exit();
}

$judge_id = $_SESSION['user_id'];

// --- Fetch Judge-Specific Statistics ---

// 1. Total videos voted on by this judge
$stmt_voted = $conn->prepare("SELECT COUNT(*) as count FROM judge_votes WHERE user_id = ?");
$stmt_voted->bind_param("i", $judge_id);
$stmt_voted->execute();
$voted_count = $stmt_voted->get_result()->fetch_assoc()['count'];

// 2. Total videos on the platform
$total_videos = $conn->query("SELECT COUNT(*) as count FROM videos")->fetch_assoc()['count'];

// 3. Judge's average score given
$stmt_avg_score = $conn->prepare("SELECT AVG(score) as avg_score FROM judge_votes WHERE user_id = ?");
$stmt_avg_score->bind_param("i", $judge_id);
$stmt_avg_score->execute();
$avg_score = $stmt_avg_score->get_result()->fetch_assoc()['avg_score'];


// --- Fetch All Videos with this Judge's Voting Status ---
// We use a LEFT JOIN to get all videos, and for each video, we check if a vote exists from the current judge.
$sql = "SELECT 
            v.id, v.title, v.thumbnail_path, v.views, v.likes,
            u.username as uploader_username,
            jv.score as judge_vote_score
        FROM videos v
        JOIN users u ON v.user_id = u.id
        LEFT JOIN judge_votes jv ON v.id = jv.video_id AND jv.user_id = ?
        ORDER BY v.uploaded_at DESC";

$stmt_videos = $conn->prepare($sql);
$stmt_videos->bind_param("i", $judge_id);
$stmt_videos->execute();
$videos_list = $stmt_videos->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_voted->close();
$stmt_avg_score->close();
$stmt_videos->close();
$conn->close();

include 'header.php';
?>
<title>Judge Dashboard - TalentUp SriLanka</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<main class="flex-grow container mx-auto px-6 py-12">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold">Judge Dashboard</h1>
                <p class="text-gray-400 text-lg mt-1">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! Review and rate the talent.</p>
            </div>
             <a href="profile.php" class="mt-4 md:mt-0 inline-block bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition">Manage Profile</a>
        </div>
        
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-gray-800 p-6 rounded-lg shadow-xl text-center">
                <p class="text-3xl font-bold text-blue-400"><?php echo number_format($voted_count); ?> / <?php echo number_format($total_videos); ?></p>
                <p class="text-gray-400 mt-1">Videos Voted On</p>
            </div>
            <div class="bg-gray-800 p-6 rounded-lg shadow-xl text-center">
                <p class="text-3xl font-bold text-blue-400"><?php echo $avg_score ? number_format($avg_score, 2) : 'N/A'; ?></p>
                <p class="text-gray-400 mt-1">Your Average Score</p>
            </div>
             <div class="bg-gray-800 p-6 rounded-lg shadow-xl text-center">
                <p class="text-3xl font-bold text-blue-400"><?php echo number_format($total_videos - $voted_count); ?></p>
                <p class="text-gray-400 mt-1">Awaiting Your Vote</p>
            </div>
        </div>

        <!-- Videos List -->
        <div class="bg-gray-800/50 p-8 rounded-lg shadow-xl">
            <h2 class="text-2xl font-semibold border-b border-gray-700 pb-4 mb-6">All Submitted Performances</h2>
             <?php if (empty($videos_list)): ?>
                <p class="text-center text-gray-400 py-10">No videos have been uploaded yet. Check back soon!</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($videos_list as $video): ?>
                        <div class="bg-gray-800 p-4 rounded-lg flex flex-col md:flex-row items-center md:space-x-4">
                            <!-- Thumbnail -->
                            <a href="video_player.php?id=<?php echo $video['id']; ?>" class="flex-shrink-0 w-full md:w-40">
                                <img src="<?php echo htmlspecialchars($video['thumbnail_path']); ?>" alt="Thumbnail for <?php echo htmlspecialchars($video['title']); ?>" class="rounded-md w-full h-24 object-cover">
                            </a>
                            <!-- Video Info -->
                            <div class="flex-grow text-center md:text-left mt-4 md:mt-0">
                                <h3 class="font-bold text-lg text-white"><?php echo htmlspecialchars($video['title']); ?></h3>
                                <p class="text-sm text-gray-400">By: <?php echo htmlspecialchars($video['uploader_username']); ?></p>
                                <div class="flex justify-center md:justify-start space-x-4 mt-2 text-sm text-gray-300">
                                    <span><i class="fas fa-eye mr-1"></i> <?php echo number_format($video['views']); ?></span>
                                    <span><i class="fas fa-thumbs-up mr-1"></i> <?php echo number_format($video['likes']); ?></span>
                                </div>
                            </div>
                            <!-- Voting Status & Action -->
                            <div class="flex-shrink-0 text-center mt-4 md:mt-0 md:w-48">
                                <?php if ($video['judge_vote_score'] !== null): ?>
                                    <div class="mb-2">
                                        <p class="text-sm text-gray-400">You Voted:</p>
                                        <p class="text-xl font-bold text-green-400"><?php echo htmlspecialchars($video['judge_vote_score']); ?> / 100</p>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-2">
                                         <p class="text-sm text-gray-400">Status:</p>
                                        <p class="text-lg font-semibold text-yellow-400">Awaiting Vote</p>
                                    </div>
                                <?php endif; ?>
                                <a href="video_player.php?id=<?php echo $video['id']; ?>" class="w-full inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition">
                                    <?php echo $video['judge_vote_score'] !== null ? 'Re-watch' : 'Watch & Vote'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>

