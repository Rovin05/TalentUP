<?php
session_start();
require 'db_connect.php';

// Authenticate: Ensure the user is logged in and is an admin.
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: login.php');
    exit();
}

// --- Fetch Site-Wide Statistics ---
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_judges = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'judge'")->fetch_assoc()['count'];
$total_videos = $conn->query("SELECT COUNT(*) as count FROM videos")->fetch_assoc()['count'];
$total_comments = $conn->query("SELECT COUNT(*) as count FROM video_comments")->fetch_assoc()['count'];
$total_likes = $conn->query("SELECT SUM(likes) as count FROM videos")->fetch_assoc()['count'];
$total_votes = $conn->query("SELECT COUNT(*) as count FROM judge_votes")->fetch_assoc()['count'];

// --- Fetch Recent Activity ---
// Latest 5 users
$latest_users = $conn->query("SELECT username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Latest 5 videos
$latest_videos = $conn->query("SELECT v.id, v.title, u.username FROM videos v JOIN users u ON v.user_id = u.id ORDER BY v.uploaded_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$conn->close();
include 'header.php';
?>
<title>Admin Dashboard - TalentUp SriLanka</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<main class="flex-grow container mx-auto px-6 py-12">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl md:text-4xl font-bold mb-8">Admin Dashboard</h1>

        <!-- Stats Overview -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-10">
            <div class="bg-gray-800 p-5 rounded-lg shadow-xl text-center"><i class="fas fa-users text-blue-400 text-2xl mb-2"></i><p class="text-3xl font-bold"><?php echo number_format($total_users); ?></p><p class="text-gray-400 text-sm">Total Users</p></div>
            <div class="bg-gray-800 p-5 rounded-lg shadow-xl text-center"><i class="fas fa-gavel text-blue-400 text-2xl mb-2"></i><p class="text-3xl font-bold"><?php echo number_format($total_judges); ?></p><p class="text-gray-400 text-sm">Judges</p></div>
            <div class="bg-gray-800 p-5 rounded-lg shadow-xl text-center"><i class="fas fa-video text-blue-400 text-2xl mb-2"></i><p class="text-3xl font-bold"><?php echo number_format($total_videos); ?></p><p class="text-gray-400 text-sm">Videos</p></div>
            <div class="bg-gray-800 p-5 rounded-lg shadow-xl text-center"><i class="fas fa-thumbs-up text-blue-400 text-2xl mb-2"></i><p class="text-3xl font-bold"><?php echo number_format($total_likes); ?></p><p class="text-gray-400 text-sm">Likes</p></div>
            <div class="bg-gray-800 p-5 rounded-lg shadow-xl text-center"><i class="fas fa-comments text-blue-400 text-2xl mb-2"></i><p class="text-3xl font-bold"><?php echo number_format($total_comments); ?></p><p class="text-gray-400 text-sm">Comments</p></div>
            <div class="bg-gray-800 p-5 rounded-lg shadow-xl text-center"><i class="fas fa-vote-yea text-blue-400 text-2xl mb-2"></i><p class="text-3xl font-bold"><?php echo number_format($total_votes); ?></p><p class="text-gray-400 text-sm">Judge Votes</p></div>
        </div>
        
        <!-- Management Sections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Column: Management & New Judge Form -->
            <div>
                <div class="bg-gray-800/50 p-6 rounded-lg shadow-xl mb-8">
                    <h2 class="text-2xl font-semibold mb-4">Management</h2>
                    <div class="space-y-3">
                        <a href="manage_users.php" class="block w-full text-left bg-gray-700 hover:bg-gray-600 p-4 rounded-lg transition"><i class="fas fa-users-cog mr-3 w-5"></i>Manage All Users</a>
                        <a href="user_dashboard.php" class="block w-full text-left bg-gray-700 hover:bg-gray-600 p-4 rounded-lg transition"><i class="fas fa-video mr-3 w-5"></i>Manage Your Videos</a>
                    </div>
                </div>

                <div class="bg-gray-800/50 p-6 rounded-lg shadow-xl">
                    <h2 class="text-2xl font-semibold mb-4">Add a New Judge</h2>
                    <form id="add-judge-form" class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-300">Username</label>
                            <input type="text" id="username" name="username" required class="mt-1 block w-full bg-gray-700 border-gray-600 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300">Email</label>
                            <input type="email" id="email" name="email" required class="mt-1 block w-full bg-gray-700 border-gray-600 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                            <input type="password" id="password" name="password" required class="mt-1 block w-full bg-gray-700 border-gray-600 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">Create Judge Account</button>
                    </form>
                    <div id="form-message" class="mt-4 text-center"></div>
                </div>
            </div>

            <!-- Right Column: Recent Activity -->
            <div>
                <div class="bg-gray-800/50 p-6 rounded-lg shadow-xl mb-8">
                    <h2 class="text-2xl font-semibold mb-4">Recently Joined Users</h2>
                    <div class="space-y-3">
                        <?php foreach($latest_users as $user): ?>
                        <div class="bg-gray-800 p-3 rounded-lg">
                            <p class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($user['email']); ?> - Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-gray-800/50 p-6 rounded-lg shadow-xl">
                    <h2 class="text-2xl font-semibold mb-4">Latest Video Submissions</h2>
                    <div class="space-y-3">
                         <?php foreach($latest_videos as $video): ?>
                            <a href="video_player.php?id=<?php echo $video['id']; ?>" class="block bg-gray-800 p-3 rounded-lg hover:bg-gray-700 transition">
                                <p class="font-semibold truncate"><?php echo htmlspecialchars($video['title']); ?></p>
                                <p class="text-sm text-gray-400">by <?php echo htmlspecialchars($video['username']); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('add-judge-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'add_judge');
    
    const messageDiv = document.getElementById('form-message');
    messageDiv.textContent = 'Creating account...';
    messageDiv.className = 'mt-4 text-center text-yellow-400';

    fetch('handle_admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.textContent = data.message;
            messageDiv.className = 'mt-4 text-center text-green-400';
            form.reset();
            // Optionally, reload the page to update stats after a delay
            setTimeout(() => location.reload(), 2000);
        } else {
            messageDiv.textContent = 'Error: ' + data.message;
            messageDiv.className = 'mt-4 text-center text-red-400';
        }
    })
    .catch(error => {
        messageDiv.textContent = 'A network error occurred.';
        messageDiv.className = 'mt-4 text-center text-red-400';
    });
});
</script>

</body>
</html>

