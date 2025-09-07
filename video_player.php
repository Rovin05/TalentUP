<?php
include 'header.php';
require 'db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: videos.php');
    exit();
}

$video_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'user';

// --- Increment View Count ---
$conn->query("UPDATE videos SET views = views + 1 WHERE id = $video_id");

// --- Fetch Video Details ---
$stmt = $conn->prepare("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    http_response_code(404);
    echo "<main class='container mx-auto px-6 py-12 text-center'><h1 class='text-4xl font-bold'>404 - Video Not Found</h1><a href='videos.php' class='mt-6 inline-block text-blue-400 hover:underline'>&larr; Back to Videos</a></main>";
    exit();
}
$video = $result->fetch_assoc();

// --- Check if current user has liked this video ---
$user_has_liked = false;
if ($user_id) {
    $like_stmt = $conn->prepare("SELECT id FROM video_likes WHERE user_id = ? AND video_id = ?");
    $like_stmt->bind_param("ii", $user_id, $video_id);
    $like_stmt->execute();
    if ($like_stmt->get_result()->num_rows > 0) {
        $user_has_liked = true;
    }
}

// --- Fetch Comments with sentiment and role ---
$comments_stmt = $conn->prepare("
    SELECT vc.comment, vc.created_at, vc.sentiment, u.username, u.role
    FROM video_comments vc
    JOIN users u ON vc.user_id = u.id
    WHERE vc.video_id = ?
    ORDER BY vc.created_at DESC
");
$comments_stmt->bind_param("i", $video_id);
$comments_stmt->execute();
$comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- For Judges: Fetch current vote and average score ---
$judge_score = null;
$average_score = 0;
if ($user_role === 'judge') {
    $vote_stmt = $conn->prepare("SELECT score FROM judge_votes WHERE user_id = ? AND video_id = ?");
    $vote_stmt->bind_param("ii", $user_id, $video_id);
    $vote_stmt->execute();
    $vote_result = $vote_stmt->get_result();
    if ($vote_result->num_rows > 0) {
        $judge_score = $vote_result->fetch_assoc()['score'];
    }
}

$avg_stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM judge_votes WHERE video_id = ?");
$avg_stmt->bind_param("i", $video_id);
$avg_stmt->execute();
$avg_result = $avg_stmt->get_result()->fetch_assoc();
$average_score = $avg_result['avg_score'] ? round($avg_result['avg_score'], 1) : 'N/A';

$stmt->close();
$conn->close();
?>
<title><?php echo htmlspecialchars($video['title']); ?> - TalentUp SriLanka</title>

<main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="lg:grid lg:grid-cols-3 lg:gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <div class="bg-black rounded-lg overflow-hidden shadow-xl mb-6 aspect-w-16 aspect-h-9">
                <video class="w-full h-full" controls autoplay controlsList="nodownload">
                    <source src="<?php echo htmlspecialchars($video['file_path']); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                <h1 class="text-2xl md:text-3xl font-bold text-white mb-2 sm:mb-0"><?php echo htmlspecialchars($video['title']); ?></h1>
                <div class="flex items-center space-x-4">
                    <button id="like-btn" data-video-id="<?php echo $video_id; ?>" class="flex items-center space-x-2 bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg transition-colors <?php echo $user_has_liked ? 'text-blue-400' : 'text-gray-300'; ?>">
                        <svg class="w-6 h-6" id="like-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M5 10h3v11H5zm15.42.61c.45-.5.68-1.16.58-1.86-.1-.71-.49-1.32-1.09-1.74l-.23-.16H16.5v-5c0-.83-.67-1.5-1.5-1.5h-1.34c-.17 0-.34.03-.5.09l-3.2 1.34V9.5c0-.83-.67-1.5-1.5-1.5H5c-.83 0-1.5.67-1.5 1.5v11c0 .83.67 1.5 1.5 1.5h1.78l.03.01h.01c.23.06.47.09.71.09h6.5c.36 0 .7-.1.99-.28.29-.18.53-.42.69-.72l2.9-5.18c.32-.57.47-1.2.43-1.83Z"/></svg>
                        <span id="like-count" class="font-semibold"><?php echo number_format($video['likes']); ?></span>
                    </button>
                    <button id="share-btn" class="flex items-center space-x-2 bg-gray-700 hover:bg-gray-600 text-gray-300 px-4 py-2 rounded-lg transition-colors">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12s-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.368a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path></svg>
                        <span>Share</span>
                    </button>
                </div>
            </div>

            <div class="border-b border-gray-700 pb-4 mb-6">
                <div class="flex items-center justify-between text-gray-400">
                    <p class="text-lg font-semibold text-white">by <?php echo htmlspecialchars($video['username']); ?></p>
                    <p><span><?php echo number_format($video['views']); ?> views</span> &bull; <span><?php echo date("M d, Y", strtotime($video['uploaded_at'])); ?></span></p>
                </div>
            </div>
            
             <!-- Judge Voting Panel -->
            <?php if ($user_role === 'judge'): ?>
            <div id="judge-panel" class="bg-yellow-900/30 border border-yellow-700 p-6 rounded-lg mb-8">
                <h3 class="text-xl font-bold text-yellow-300 mb-4">Judge's Panel</h3>
                <form id="vote-form" class="space-y-4">
                     <label for="vote-slider" class="block text-gray-300">Your Score: <span id="slider-value" class="font-bold text-yellow-300 text-lg"><?php echo $judge_score ?? 50; ?></span> / 100</label>
                    <input type="range" id="vote-slider" name="score" min="0" max="100" value="<?php echo $judge_score ?? 50; ?>" class="w-full h-3 bg-gray-700 rounded-lg appearance-none cursor-pointer range-lg">
                    <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-5 rounded-lg transition-colors">Submit Vote</button>
                    <span id="vote-feedback" class="text-green-400 ml-4"></span>
                </form>
                 <div class="mt-4 text-gray-300">
                    Current Average Score: <strong id="average-score" class="text-xl text-white"><?php echo $average_score; ?></strong>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-gray-800 p-4 rounded-lg mb-8"><p class="text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($video['description']); ?></p></div>
            
            <!-- Comments Section -->
            <div>
                <h2 class="text-2xl font-bold mb-4">Comments (<?php echo count($comments); ?>)</h2>
                <?php if ($user_id): ?>
                <form id="comment-form" class="mb-6">
                    <textarea name="comment" id="comment-box" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Add a public comment..." required></textarea>
                    <button type="submit" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition">Comment</button>
                </form>
                <?php else: ?>
                    <p class="text-gray-400 mb-6"><a href="login.php" class="text-blue-400 hover:underline">Log in</a> to post a comment.</p>
                <?php endif; ?>

                <div id="comments-container" class="space-y-4">
                    <?php if (empty($comments)): ?>
                        <p id="no-comments" class="text-gray-500">Be the first to comment on this video!</p>
                    <?php else: ?>
                        <?php foreach($comments as $comment): ?>
                        <?php
                        $sent = strtolower($comment['sentiment'] ?? 'neutral');
                        $sentLabel = match($sent) {
                          'positive' => '<span class="text-green-400 text-sm ml-2">(Positive)</span>',
                          'negative' => '<span class="text-red-400 text-sm ml-2">(Negative)</span>',
                          default => '<span class="text-gray-400 text-sm ml-2">(Neutral)</span>',
                        };
                        $role = strtolower($comment['role'] ?? 'user');
                        $roleBadge = $role === 'judge'
                            ? '<span class="ml-2 text-xs bg-yellow-500 text-black px-2 py-1 rounded-full">Judge</span>'
                            : '<span class="ml-2 text-xs bg-blue-500 text-white px-2 py-1 rounded-full">User</span>';
                        ?>
                        <div class="flex items-start space-x-4">
                             <div class="w-10 h-10 bg-purple-500 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-lg"><?php echo strtoupper(substr($comment['username'], 0, 1)); ?></div>
                            <div>
                                <p class="font-semibold text-white">
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                    <?php echo $roleBadge; ?>
                                    <?php echo $sentLabel; ?>
                                    <span class="text-sm text-gray-400 font-normal"><?php echo date("M d, Y", strtotime($comment['created_at'])); ?></span>
                                </p>
                                <p class="text-gray-300"><?php echo htmlspecialchars($comment['comment']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="lg:col-span-1 mt-8 lg:mt-0"><h2 class="text-2xl font-bold mb-4">Up Next</h2><!-- Placeholder --></div>
    </div>
</main>

<!-- Share Modal -->
<div id="share-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4 text-white">Share this video</h3>
        <input id="share-link" type="text" readonly class="w-full bg-gray-700 rounded-md px-3 py-2 text-white border border-gray-600" value="<?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
        <button id="copy-btn" class="mt-4 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg w-full">Copy Link</button>
        <button id="close-share" class="mt-2 text-gray-400 hover:text-white w-full">Close</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const likeBtn = document.getElementById('like-btn');
    const likeCountSpan = document.getElementById('like-count');
    let liked = <?php echo $user_has_liked ? 'true' : 'false'; ?>;

    if (likeBtn) {
        likeBtn.addEventListener('click', () => {
            if (!liked) {
                fetch('like_video.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({video_id: likeBtn.dataset.videoId})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        liked = true;
                        likeCountSpan.textContent = data.likes;
                        likeBtn.classList.remove('text-gray-300');
                        likeBtn.classList.add('text-blue-400');
                    }
                });
            }
        });
    }

    // Share modal handlers
    const shareBtn = document.getElementById('share-btn');
    const shareModal = document.getElementById('share-modal');
    const closeShareBtn = document.getElementById('close-share');
    const copyBtn = document.getElementById('copy-btn');
    const shareLinkInput = document.getElementById('share-link');

    shareBtn.addEventListener('click', () => shareModal.classList.remove('hidden'));
    closeShareBtn.addEventListener('click', () => shareModal.classList.add('hidden'));
    copyBtn.addEventListener('click', () => {
        shareLinkInput.select();
        document.execCommand('copy');
        copyBtn.textContent = 'Copied!';
        setTimeout(() => { copyBtn.textContent = 'Copy Link'; }, 2000);
    });

    // Judge vote slider update
    const voteSlider = document.getElementById('vote-slider');
    const sliderValue = document.getElementById('slider-value');
    const voteForm = document.getElementById('vote-form');
    const voteFeedback = document.getElementById('vote-feedback');
    const averageScoreDisplay = document.getElementById('average-score');

    if (voteSlider && sliderValue) {
        voteSlider.addEventListener('input', () => {
            sliderValue.textContent = voteSlider.value;
        });
    }

    if (voteForm) {
        voteForm.addEventListener('submit', e => {
            e.preventDefault();
            voteFeedback.textContent = '';
            const formData = new FormData(voteForm);

            fetch('submit_vote.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    voteFeedback.textContent = 'Vote submitted successfully!';
                    averageScoreDisplay.textContent = data.average_score;
                } else {
                    voteFeedback.textContent = 'Error submitting vote.';
                }
            });
        });
    }

    // --- Comment Submission & Display ---
    const commentForm = document.getElementById('comment-form');
    const commentsContainer = document.getElementById('comments-container');
    const noCommentsMessage = document.getElementById('no-comments');

    if (commentForm) {
        commentForm.addEventListener('submit', e => {
            e.preventDefault();
            const commentBox = document.getElementById('comment-box');
            const commentText = commentBox.value.trim();
            if (!commentText) return;

            fetch('submit_comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    video_id: <?php echo $video_id; ?>,
                    comment: commentText
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    commentBox.value = '';
                    if (noCommentsMessage) noCommentsMessage.style.display = 'none';

                    // Build sentiment and role badges
                    const sentimentMap = {
                        positive: '<span class="text-green-400 text-sm ml-2">(Positive)</span>',
                        negative: '<span class="text-red-400 text-sm ml-2">(Negative)</span>',
                        neutral: '<span class="text-gray-400 text-sm ml-2">(Neutral)</span>'
                    };
                    const roleBadge = (data.comment.role === 'judge')
                        ? '<span class="ml-2 text-xs bg-yellow-500 text-black px-2 py-1 rounded-full">Judge</span>'
                        : '<span class="ml-2 text-xs bg-blue-500 text-white px-2 py-1 rounded-full">User</span>';
                    const sentimentLabel = sentimentMap[data.comment.sentiment] || '';

                    const newCommentHTML = `
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center font-bold text-lg">${data.comment.username.charAt(0).toUpperCase()}</div>
                            <div>
                                <p class="font-semibold text-white">
                                    ${data.comment.username}
                                    ${roleBadge}
                                    ${sentimentLabel}
                                    <span class="text-sm text-gray-400 font-normal">${new Date(data.comment.created_at).toLocaleDateString()}</span>
                                </p>
                                <p class="text-gray-300">${data.comment.comment}</p>
                            </div>
                        </div>
                    `;
                    commentsContainer.insertAdjacentHTML('afterbegin', newCommentHTML);
                } else {
                    alert('Failed to submit comment.');
                }
            });
        });
    }
});
</script>
