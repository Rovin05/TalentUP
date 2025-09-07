<?php
session_start();
require 'db_connect.php';

// --- Fetch Dynamic Content ---

// 1. Fetch latest 4 videos with uploader's username
$latest_videos = $conn->query(
    "SELECT v.id, v.title, v.thumbnail_path, u.username 
     FROM videos v 
     JOIN users u ON v.user_id = u.id 
     ORDER BY v.uploaded_at DESC 
     LIMIT 4"
)->fetch_all(MYSQLI_ASSOC);

// 2. Fetch up to 4 judges
$judges = $conn->query(
    "SELECT username, email 
     FROM users 
     WHERE role = 'judge' 
     LIMIT 4"
)->fetch_all(MYSQLI_ASSOC);

// 3. Fetch site statistics
$total_contestants = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM videos")->fetch_assoc()['count'];
$total_videos = $conn->query("SELECT COUNT(*) as count FROM videos")->fetch_assoc()['count'];

$conn->close();

include 'header.php';
?>
<title>TalentUp SriLanka - Online Talent Show</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
    /* Custom styles for the chatbot */
    #chat-icon {
        position: fixed;
        bottom: 25px;
        right: 25px;
        background-color: #2563eb;
        color: white;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 28px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 1000;
        transition: transform 0.2s ease-in-out;
    }
    #chat-icon:hover {
        transform: scale(1.1);
    }
    #chat-window {
        position: fixed;
        bottom: 100px;
        right: 25px;
        width: 350px;
        height: 500px;
        background-color: #1f2937; /* bg-gray-800 */
        border: 1px solid #374151; /* border-gray-700 */
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        display: none;
        flex-direction: column;
        z-index: 999;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    #chat-window.active {
        display: flex;
        opacity: 1;
        transform: translateY(0);
    }
    #chat-header {
        background-color: #374151; /* bg-gray-700 */
        color: white;
        padding: 1rem;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #close-chat {
        cursor: pointer;
        font-size: 1.2rem;
    }
    #chat-messages {
        flex-grow: 1;
        padding: 1rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .chat-message {
        padding: 0.75rem 1rem;
        border-radius: 10px;
        max-width: 80%;
        line-height: 1.5;
    }
    .bot-message {
        background-color: #374151; /* bg-gray-700 */
        color: #d1d5db; /* text-gray-300 */
        align-self: flex-start;
    }
    .user-message {
        background-color: #2563eb; /* bg-blue-600 */
        color: white;
        align-self: flex-end;
    }
    #chat-form {
        display: flex;
        padding: 1rem;
        border-top: 1px solid #374151; /* border-gray-700 */
    }
    #chat-input {
        flex-grow: 1;
        background-color: #4b5563; /* bg-gray-600 */
        border: 1px solid #6b7280; /* border-gray-500 */
        color: white;
        border-radius: 8px;
        padding: 0.75rem;
        outline: none;
    }
    #chat-input::placeholder {
        color: #9ca3af; /* text-gray-400 */
    }
    #chat-submit {
        background-color: #2563eb;
        border: none;
        color: white;
        padding: 0 1rem;
        border-radius: 8px;
        margin-left: 0.5rem;
        cursor: pointer;
    }
</style>

<!-- Hero Section -->
<section class="bg-gray-900 text-white text-center py-20 md:py-32">
    <div class="container mx-auto px-6">
        <h1 class="text-4xl md:text-6xl font-bold leading-tight">Welcome to TalentUp SriLanka</h1>
        <p class="text-lg md:text-xl mt-4 mb-8 text-gray-300">Your Stage, Your Moment. The Biggest Online Talent Show is Here!</p>
        <div class="space-x-4">
            <a href="register.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition">Get Started</a>
            <a href="videos.php" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-lg text-lg transition">Watch Talents</a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="bg-gray-800 py-12">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-center">
            <div class="p-4">
                <p class="text-4xl font-bold text-blue-400"><?php echo number_format($total_contestants); ?>+</p>
                <p class="text-gray-400 text-lg">Amazing Contestants</p>
            </div>
            <div class="p-4">
                <p class="text-4xl font-bold text-blue-400"><?php echo number_format($total_videos); ?>+</p>
                <p class="text-gray-400 text-lg">Videos Submitted</p>
            </div>
        </div>
    </div>
</section>

<!-- Latest Videos Section -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-bold text-center text-white mb-12">Latest Performances</h2>
        <?php if (!empty($latest_videos)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($latest_videos as $video): ?>
                    <div class="bg-gray-800 rounded-lg overflow-hidden shadow-xl transform hover:-translate-y-2 transition-transform duration-300">
                        <a href="video_player.php?id=<?php echo $video['id']; ?>">
                            <img src="<?php echo htmlspecialchars($video['thumbnail_path']); ?>" alt="Thumbnail for <?php echo htmlspecialchars($video['title']); ?>" class="w-full h-48 object-cover">
                            <div class="p-4">
                                <h3 class="font-bold text-white truncate"><?php echo htmlspecialchars($video['title']); ?></h3>
                                <p class="text-gray-400 text-sm">by <?php echo htmlspecialchars($video['username']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
             <div class="text-center mt-12">
                <a href="videos.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition">View All Videos</a>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-400">No videos have been uploaded yet. Be the first!</p>
        <?php endif; ?>
    </div>
</section>

<!-- Judges Section -->
<section class="py-20 bg-gray-800">
    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-bold text-center text-white mb-12">Meet Our Judges</h2>
        <?php if (!empty($judges)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($judges as $judge): ?>
                    <div class="bg-gray-900 rounded-lg p-6 text-center shadow-xl">
                        <div class="w-24 h-24 rounded-full bg-blue-500 mx-auto flex items-center justify-center text-3xl font-bold mb-4">
                            <?php echo strtoupper(substr($judge['username'], 0, 1)); ?>
                        </div>
                        <h3 class="font-bold text-xl text-white"><?php echo htmlspecialchars($judge['username']); ?></h3>
                        <p class="text-gray-400">Official Judge</p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-12">
                <a href="judges.php" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition">See All Judges</a>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-400">Judges will be announced soon. Stay tuned!</p>
        <?php endif; ?>
    </div>
</section>

<!-- Chatbot HTML -->
<div id="chat-icon">
    <i class="fas fa-comment-dots"></i>
</div>

<div id="chat-window">
    <div id="chat-header">
        <span>TalentUp Assistant</span>
        <span id="close-chat">&times;</span>
    </div>
    <div id="chat-messages">
        <div class="chat-message bot-message">
            Hello! How can I help you today?
        </div>
    </div>
    <form id="chat-form">
        <input type="text" id="chat-input" placeholder="Ask a question..." autocomplete="off">
        <button type="submit" id="chat-submit"><i class="fas fa-paper-plane"></i></button>
    </form>
</div>


<script>
// --- Chatbot JavaScript ---
const chatIcon = document.getElementById('chat-icon');
const chatWindow = document.getElementById('chat-window');
const closeChat = document.getElementById('close-chat');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatMessages = document.getElementById('chat-messages');

// Toggle chat window
chatIcon.addEventListener('click', () => {
    chatWindow.classList.toggle('active');
});

closeChat.addEventListener('click', () => {
    chatWindow.classList.remove('active');
});

// Handle form submission
chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const userMessage = chatInput.value.trim();
    if (userMessage === '') return;

    appendMessage(userMessage, 'user');
    chatInput.value = '';

    // Get bot response after a short delay
    setTimeout(() => {
        const botResponse = getBotResponse(userMessage);
        appendMessage(botResponse, 'bot');
    }, 500);
});

// Append a message to the chat window
function appendMessage(message, sender) {
    const messageElement = document.createElement('div');
    messageElement.classList.add('chat-message', `${sender}-message`);
    messageElement.textContent = message;
    chatMessages.appendChild(messageElement);
    chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll to bottom
}

// Simple bot logic
function getBotResponse(userInput) {
    const input = userInput.toLowerCase();

    if (input.includes('hello') || input.includes('hi')) {
        return 'Hello there! How can I assist you with TalentUp SriLanka?';
    } else if (input.includes('register') || input.includes('sign up')) {
        return 'You can register for an account by clicking the "Get Started" or "Sign Up" button on the top navigation bar.';
    } else if (input.includes('upload') || input.includes('submit video')) {
        return 'Once you are logged in, you can upload your video from your user dashboard. Look for the "Upload" link!';
    } else if (input.includes('judges')) {
        return 'We have a panel of experienced judges. You can see them on our "Judges" page.';
    } else if (input.includes('rules') || input.includes('guidelines')) {
        return 'Please ensure your video is under 5 minutes, family-friendly, and showcases your unique talent!';
    } else if (input.includes('vote') || input.includes('like')) {
        return 'You can vote for and like videos on the video player page. Your support helps contestants!';
    } else if (input.includes('thanks') || input.includes('thank you')) {
        return 'You\'re welcome! Is there anything else I can help you with?';
    } else {
        return 'I am not sure how to answer that. You can try asking about registration, uploading, judges, or voting.';
    }
}

</script>
</body>
</html>