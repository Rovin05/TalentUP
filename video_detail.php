<?php
// We don't need a strict session check here, as this is a public page.
// However, starting the session allows us to know if a user is logged in
// which can be used for features like showing a "liked" status on videos.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Detail - TalentUp Sri Lanka</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-hue: 215;
            --secondary-hue: 170;
            --primary-color: hsl(var(--primary-hue), 78%, 51%);
            --secondary-color: hsl(var(--secondary-hue), 85%, 41%);
            --text-dark: #111827;
            --text-light: #4B5563;
            --bg-white: #FFFFFF;
            --bg-light: #F9FAFB;
            --border-color: #E5E7EB;
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.7;
            color: var(--text-dark);
            background: var(--bg-light);
            overflow-x: hidden;
        }
        
        /* --- Animated Background --- */
        .shape-container {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; z-index: -1; overflow: hidden;
        }
        .shape {
            position: absolute; border-radius: 50%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            opacity: 0.1; animation: float 20s infinite linear alternate;
        }
        .shape1 { width: 40vw; height: 40vw; max-width: 500px; max-height: 500px; top: -10vh; right: -10vw; animation-duration: 25s; }
        .shape2 { width: 50vw; height: 50vw; max-width: 700px; max-height: 700px; bottom: -20vh; left: -20vw; animation-duration: 30s; }
        @keyframes float {
            from { transform: translateY(0px) rotate(0deg); }
            to { transform: translateY(-20px) rotate(90deg); }
        }

        /* --- Header & Navigation --- */
        header {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm); position: sticky; top: 0; z-index: 1000;
        }
        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 5%; max-width: 1400px; margin: 0 auto;
        }
        .logo { font-size: 1.75rem; font-weight: 800; color: var(--primary-color); text-decoration: none; }
        .nav-links { display: none; list-style: none; gap: 0.5rem; align-items: center; }
        @media (min-width: 992px) { .nav-links { display: flex; } }
        .nav-links > li > a {
            color: var(--text-dark); text-decoration: none; font-weight: 500;
            transition: var(--transition); position: relative; padding: 0.5rem 1rem;
            border-radius: 8px; display: flex; align-items: center; gap: 0.5rem;
        }
        .nav-links > li > a:hover { color: var(--primary-color); background-color: hsl(var(--primary-hue), 78%, 95%); }
        .nav-links > li > a.active { color: var(--primary-color); font-weight: 700; background-color: hsl(var(--primary-hue), 78%, 95%);}
        .nav-right { display: flex; align-items: center; gap: 1rem; }
        
        .user-menu-container { position: relative; }
        .user-menu-button { display: flex; align-items: center; gap: 0.5rem; background: transparent; border: none; cursor: pointer; font-family: inherit; font-size: 1rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 8px; transition: var(--transition); }
        .user-menu-button:hover { background-color: hsl(var(--primary-hue), 78%, 95%); color: var(--primary-color); }
        .user-menu-button .fa-user-circle { font-size: 1.5rem; }
        .user-menu-button .fa-chevron-down { font-size: 0.8rem; transition: var(--transition); margin-left: 0.25rem; }
        .user-menu-container.active .user-menu-button .fa-chevron-down { transform: rotate(180deg); }
        .user-menu-dropdown { position: absolute; top: calc(100% + 10px); right: 0; background: var(--bg-white); border-radius: 8px; box-shadow: var(--shadow-lg); min-width: 220px; padding: 0.5rem; z-index: 1010; opacity: 0; visibility: hidden; transform: translateY(10px); transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s; }
        .user-menu-container.active .user-menu-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .user-menu-dropdown a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; text-decoration: none; color: var(--text-dark); border-radius: 6px; transition: var(--transition); }
        .user-menu-dropdown a:hover { background-color: hsl(var(--primary-hue), 78%, 95%); color: var(--primary-color); }
        .user-menu-dropdown .dropdown-divider { height: 1px; background-color: var(--border-color); margin: 0.5rem 0; }
        .user-menu-dropdown .fa-fw { width: 20px; text-align: center; }

        .hamburger { display: flex; cursor: pointer; flex-direction: column; gap: 5px; }
        @media (min-width: 992px) { .hamburger { display: none; } }
        .hamburger .bar { width: 25px; height: 3px; background-color: var(--text-dark); border-radius: 5px; transition: var(--transition); }
        .mobile-nav { position: fixed; top: 68px; left: -100%; width: 100%; height: calc(100vh - 68px); background: var(--bg-white); display: flex; flex-direction: column; align-items: center; padding-top: 2rem; transition: left 0.4s ease-in-out; z-index: 999; }
        .mobile-nav.active { left: 0; }
        .mobile-nav ul { list-style: none; width: 100%; text-align: center; }
        .mobile-nav ul li { padding: 1rem 0; }
        .mobile-nav ul a { text-decoration: none; color: var(--text-dark); font-size: 1.5rem; font-weight: 500; }
        
        /* --- Main Content --- */
        .main-container {
            max-width: 1400px; margin: 2rem auto; padding: 0 5%;
            display: grid; grid-template-columns: 1fr; gap: 2rem;
        }
        @media (min-width: 1024px) { .main-container { grid-template-columns: minmax(0, 2.5fr) minmax(0, 1fr); } }

        /* Video Section */
        .video-player { aspect-ratio: 16 / 9; width: 100%; background: #000; border-radius: 12px; margin-bottom: 1.5rem; overflow: hidden; box-shadow: var(--shadow-lg); }
        .video-player video { width: 100%; height: 100%; display: block; }
        .video-title { font-size: 2rem; font-weight: 700; margin-bottom: 1rem; }
        .video-meta { display: flex; align-items: center; gap: 1.5rem; color: var(--text-light); margin-bottom: 1.5rem; flex-wrap: wrap; }
        .uploader-info { display: flex; align-items: center; gap: 0.75rem; font-weight: 500; }
        .uploader-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--border-color); display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .video-description { line-height: 1.8; margin-bottom: 1.5rem; }
        .video-actions { display: flex; gap: 1rem; align-items: center; padding-bottom: 1.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; transition: var(--transition); border: 2px solid transparent; }
        .like-button { background: var(--border-color); color: var(--text-light); }
        .like-button.liked { background: #FEE2E2; color: #DC2626; }
        .like-button .fa-heart { transition: transform 0.2s ease; }
        .like-button.liked .fa-heart { transform: scale(1.2); }

        /* Comments */
        .comments-section h3 { font-size: 1.5rem; margin-bottom: 1.5rem; }
        .comment-form-wrapper { display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 2rem; }
        #comment-form { flex-grow: 1; }
        #comment-form textarea { width: 100%; min-height: 100px; padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); resize: vertical; margin-bottom: 1rem; font-family: inherit; font-size: 1rem; }
        #comment-form textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px hsla(var(--primary-hue), 78%, 51%, 0.2); }
        #comment-form button { background-color: var(--primary-color); color: white; padding: 0.75rem 1.5rem; }
        #comment-form button:disabled { background-color: var(--text-light); cursor: not-allowed; }
        .comment { display: flex; gap: 1rem; padding: 1.5rem 0; border-bottom: 1px solid var(--border-color); }
        .comment:last-child { border-bottom: none; }
        .comment-avatar, .current-user-avatar { flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; background: var(--border-color); display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .comment-author { font-weight: 600; }
        .comment-date { font-size: 0.85rem; color: var(--text-light); margin-left: 0.5rem; }
        .comment-text { margin-top: 0.25rem; }

        /* Sidebar */
        .sidebar-card { background: white; border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.5rem; }
        .sidebar-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; }
        .up-next-list .video-item { display: flex; gap: 1rem; margin-bottom: 1rem; border-radius: 8px; transition: var(--transition); }
        .up-next-list .video-item:hover { background-color: var(--bg-light); }
        .up-next-list .video-item:last-child { margin-bottom: 0; }
        .up-next-thumbnail { flex-shrink: 0; width: 120px; height: 67px; border-radius: 8px; object-fit: cover; }
        .up-next-info h4 { font-size: 0.9rem; font-weight: 500; margin: 0 0 0.25rem; line-height: 1.4; }
        .up-next-info p { font-size: 0.8rem; color: var(--text-light); }
        
        .hidden { display: none !important; }

    </style>
</head>
<body>
    <div class="shape-container">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
    </div>
    <header>
        <nav>
            <a href="index.php" class="logo">TalentUp</a>
            <ul class="nav-links"><!-- Populated by JS --></ul>
            <div class="nav-right">
                 <div class="hamburger" id="hamburger-menu">
                    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
                </div>
            </div>
        </nav>
         <div class="mobile-nav" id="mobile-menu"><!-- Populated by JS --></div>
    </header>

    <main class="main-container">
        <div class="primary-content">
            <div class="video-player">
                <video id="videoElement" controls controlsList="nodownload" style="display: none;"></video>
                <div id="videoLoading">Loading video...</div>
            </div>
            <div class="video-info">
                <h1 class="video-title" id="videoTitle"></h1>
                <div class="video-meta">
                    <div class="uploader-info">
                        <div class="uploader-avatar" id="uploaderAvatar"></div>
                        <span id="videoUploader"></span>
                    </div>
                    <span><i class="far fa-calendar-alt"></i> <span id="videoDate"></span></span>
                </div>
                <div class="video-actions">
                    <button class="btn like-button" id="likeButton">
                        <i class="far fa-heart"></i>
                        <span>Like (<span id="likeCount">0</span>)</span>
                    </button>
                    <div class="action-item"><i class="fas fa-eye"></i> <span id="viewCount">0</span> Views</div>
                    <div class="action-item"><i class="fas fa-comment"></i> <span id="commentCount">0</span> Comments</div>
                </div>
                <p class="video-description" id="videoDescription"></p>
                <div class="comments-section">
                    <h3 id="commentsHeader">Comments</h3>
                    <div id="comment-form-container"></div>
                    <div class="comment-list" id="commentList"></div>
                </div>
            </div>
        </div>
        <aside class="sidebar">
            <div class="sidebar-card hidden" id="judgeFeedbackCard">
                <h3 class="sidebar-title">Judge Feedback</h3>
                <!-- Feedback UI here -->
            </div>
             <div class="sidebar-card" id="upNextCard">
                <h3 class="sidebar-title">Up Next</h3>
                <div class="up-next-list" id="upNextList"></div>
            </div>
        </aside>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', async function() {
        const videoId = new URLSearchParams(window.location.search).get('id');
        if (!videoId) {
            document.querySelector('.main-container').innerHTML = '<h1>Error: No video ID provided.</h1>';
            return;
        }
        await init(videoId);
    });
    
    async function init(videoId) {
        document.getElementById('hamburger-menu').addEventListener('click', () => {
            document.getElementById('hamburger-menu').classList.toggle('active');
            document.getElementById('mobile-menu').classList.toggle('active');
        });
        document.getElementById('likeButton').addEventListener('click', () => toggleLike(videoId));

        await updateNavigation();
        await loadVideoDetails(videoId);
        incrementViewCount(videoId);
    }

    async function loadVideoDetails(videoId) {
        try {
            const response = await fetch(`php_api/get_video_details.php?id=${videoId}`, { credentials: 'include' });
            if (!response.ok) throw new Error(`Network response was not ok. Status: ${response.status}`);
            
            const data = await response.json();
            console.log("Full server response for video details:", data);
            
            if (!data.success) throw new Error(data.message);

            const { video, comments, is_logged_in, current_user, user_role, related_videos } = data;

            document.title = `${video.title} - TalentUp Sri Lanka`;
            document.getElementById('videoTitle').textContent = video.title;
            document.getElementById('videoUploader').textContent = `By ${video.uploader_name}`;
            document.getElementById('uploaderAvatar').textContent = video.uploader_name.charAt(0).toUpperCase();
            document.getElementById('videoDate').textContent = new Date(video.uploaded_at).toLocaleDateString();
            document.getElementById('videoDescription').textContent = video.description || 'No description provided.';
            
            const videoElement = document.getElementById('videoElement');
            videoElement.src = video.file_path;
            videoElement.style.display = 'block';
            document.getElementById('videoLoading').style.display = 'none';
            
            document.getElementById('viewCount').textContent = video.views;
            updateLikeButton(video.user_has_liked, video.likes);

            renderComments(comments, is_logged_in, current_user, videoId);
            document.getElementById('commentsHeader').textContent = `${video.comment_count} Comments`;
            document.getElementById('commentCount').textContent = video.comment_count;

            renderUpNext(related_videos);

            if (user_role && ['judge', 'admin', 'super_admin'].includes(user_role)) {
                document.getElementById('judgeFeedbackCard').classList.remove('hidden');
            }

        } catch (error) {
            console.error('Failed to load video details:', error);
            document.querySelector('.primary-content').innerHTML = `<h1>Error loading video</h1><p>${error.message}</p>`;
        }
    }

    function renderComments(comments, isLoggedIn, currentUser, videoId) {
        const list = document.getElementById('commentList');
        const formContainer = document.getElementById('comment-form-container');
        
        if (isLoggedIn && currentUser) {
            formContainer.innerHTML = `
                <div class="comment-form-wrapper">
                    <div class="current-user-avatar">${currentUser.username.charAt(0).toUpperCase()}</div>
                    <form id="comment-form">
                        <textarea name="comment" placeholder="Add a public comment..." required></textarea>
                        <button type="submit" class="btn">Post Comment</button>
                    </form>
                </div>`;
            document.getElementById('comment-form').addEventListener('submit', (e) => handleCommentSubmit(e, videoId));
        } else {
            formContainer.innerHTML = `<p><a href="login.php?redirect_url=${encodeURIComponent(window.location.href)}">Log in</a> to post a comment.</p>`;
        }

        if (!comments || comments.length === 0) {
            list.innerHTML = isLoggedIn ? '<p>Be the first to comment!</p>' : '<p>No comments have been posted yet.</p>';
        } else {
            list.innerHTML = comments.map(c => `
                <div class="comment">
                    <div class="comment-avatar">${c.username.charAt(0).toUpperCase()}</div>
                    <div class="comment-content">
                        <p><span class="comment-author">${c.username}</span><span class="comment-date">${new Date(c.created_at).toLocaleString()}</span></p>
                        <p class="comment-text">${c.comment}</p>
                    </div>
                </div>`).join('');
        }
    }
    
    function renderUpNext(videos) {
        const list = document.getElementById('upNextList');
        if (!videos || videos.length === 0) {
             list.innerHTML = '<p>No related videos found.</p>';
             return;
        }
        list.innerHTML = videos.map(v => `
            <a href="video_detail.php?id=${v.id}" class="video-item" style="text-decoration:none; color:inherit;">
                <img src="${v.thumbnail_path || 'https://placehold.co/120x67'}" class="up-next-thumbnail" alt="${v.title}">
                <div class="up-next-info">
                    <h4>${v.title}</h4>
                    <p>${v.uploader_name}</p>
                </div>
            </a>
        `).join('');
    }

    async function handleCommentSubmit(event, videoId) {
        event.preventDefault();
        const form = event.target;
        const textarea = form.querySelector('textarea[name="comment"]');
        const button = form.querySelector('button');
        const commentText = textarea.value;
        if (!commentText.trim()) return;
        
        button.disabled = true;
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Posting...`;
        textarea.disabled = true;

        try {
            const response = await fetch('php_api/add_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId, comment: commentText }),
                credentials: 'include'
            });
            const result = await response.json();
            if (result.success) {
                loadVideoDetails(videoId);
            } else {
                alert('Error: ' + result.message);
                button.disabled = false;
                button.innerHTML = `Post Comment`;
                textarea.disabled = false;
            }
        } catch (error) {
            console.error("Failed to submit comment:", error);
            alert('An error occurred. Please try again.');
            button.disabled = false;
            button.innerHTML = `Post Comment`;
            textarea.disabled = false;
        }
    }
    
    async function toggleLike(videoId) {
        try {
            const response = await fetch('php_api/toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId }),
                credentials: 'include'
            });
            const data = await response.json();
            if (data.success) {
                updateLikeButton(data.user_has_liked, data.new_like_count);
            } else if (data.message.includes('log in')) {
                window.location.href = `login.php?redirect_url=${encodeURIComponent(window.location.href)}`;
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        }
    }

    function updateLikeButton(liked, count) {
        const btn = document.getElementById('likeButton');
        const icon = btn.querySelector('i');
        btn.classList.toggle('liked', liked);
        icon.className = liked ? 'fas fa-heart' : 'far fa-heart';
        document.getElementById('likeCount').textContent = count;
    }
    
    async function incrementViewCount(videoId) {
        try {
            await fetch(`php_api/increment_view.php?id=${videoId}`, { method: 'POST', credentials: 'include' });
        } catch (error) {
            console.error("Could not increment view count:", error);
        }
    }

    const updateNavigation = async () => {
        const desktopNavContainer = document.querySelector('.nav-links');
        const mobileMenu = document.getElementById('mobile-menu');
        try {
            const response = await fetch('php_api/check_session.php');
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            
            let navLinks = '';
            let mobileNavLinks = '';
            
            if (data.loggedIn && data.user) {
                const user = data.user;
                let adminLinks = (user.role === 'super_admin' || user.role === 'admin') ? `<a href="admin_panel.php"><i class="fas fa-cog fa-fw"></i> Admin Panel</a>` : '';
                if (user.role === 'super_admin' || user.role === 'admin' || user.role === 'judge') {
                    adminLinks += `<a href="judge_panel.php"><i class="fas fa-gavel fa-fw"></i> Judge Panel</a>`;
                }
                
                navLinks = `
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="video_list.php"><i class="fas fa-play-circle"></i> Videos</a></li>
                    <li><a href="dashboard_user.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                    <li class="user-menu-container">
                        <button class="user-menu-button">
                            <i class="fas fa-user-circle"></i>
                            <span>${user.username}</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-menu-dropdown">
                            <a href="profile.php"><i class="fas fa-user fa-fw"></i> My Profile</a>
                            ${adminLinks ? '<div class="dropdown-divider"></div>' + adminLinks : ''}
                            <div class="dropdown-divider"></div>
                            <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a>
                        </div>
                    </li>`;
                
                mobileNavLinks = `
                    <li><a href="index.php">Home</a></li>
                    <li><a href="video_list.php">Videos</a></li>
                    <li><a href="dashboard_user.php">Dashboard</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    ${(user.role === 'super_admin' || user.role === 'admin') ? `<li><a href="admin_panel.php">Admin Panel</a></li>` : ''}
                    ${(user.role === 'super_admin' || user.role === 'admin' || user.role === 'judge') ? `<li><a href="judge_panel.php">Judge Panel</a></li>` : ''}
                    <li><a href="#" id="logoutBtnMobile">Logout</a></li>`;

            } else {
                navLinks = `
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="video_list.php"><i class="fas fa-play-circle"></i> Videos</a></li>
                    <li><a href="index.php#about"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php" class="btn btn-primary" style="color:white;padding: 0.5rem 1rem;"><i class="fas fa-user-plus"></i> Register</a></li>`;

                mobileNavLinks = `
                    <li><a href="index.php">Home</a></li>
                    <li><a href="video_list.php">Videos</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>`;
            }
            desktopNavContainer.innerHTML = navLinks;
            mobileMenu.innerHTML = `<ul>${mobileNavLinks}</ul>`;
        } catch (error) {
            console.error('Error updating navigation:', error);
            desktopNavContainer.innerHTML = `<li><a href="login.php">Login</a></li>`;
        }
    };
    
    const logout = () => {
        fetch('php_api/auth_logout.php', { method: 'POST' }).finally(() => window.location.href = 'login.php');
    };
    
    document.body.addEventListener('click', function(e) {
        const userMenuContainer = e.target.closest('.user-menu-container');
        if (e.target.closest('.user-menu-button')) {
            e.preventDefault();
            userMenuContainer.classList.toggle('active');
        }
        if (!userMenuContainer) {
            document.querySelectorAll('.user-menu-container.active').forEach(menu => menu.classList.remove('active'));
        }
        if (e.target.matches('#logoutBtn, #logoutBtnMobile') || e.target.closest('#logoutBtn, #logoutBtnMobile')) {
            e.preventDefault();
            logout();
        }
    });
    </script>
</body>
</html>

