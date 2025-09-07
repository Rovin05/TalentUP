<?php
// Use the centralized session check to protect this page.
require_once 'php_api/session_check.php';

// The session_check.php script will redirect if the user is not logged in.
// If the script continues, we know the user is authenticated.
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TalentUp Sri Lanka</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-hue: 215;
            --secondary-hue: 280;
            --primary-color: hsl(var(--primary-hue), 78%, 51%);
            --primary-dark: hsl(var(--primary-hue), 78%, 45%);
            --primary-light: hsl(var(--primary-hue), 78%, 95%);
            --text-dark: #111827;
            --text-light: #6B7280;
            --bg-white: #FFFFFF;
            --bg-light: #F9FAFB;
            --border-color: #E5E7EB;
            --success: #10B981;
            --error: #EF4444;
            --border-radius-lg: 16px;
            --border-radius-md: 8px;
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }
        
        /* --- Sidebar Navigation --- */
        .sidebar {
            width: 260px;
            background: var(--bg-white);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            transition: transform 0.3s ease;
            position: fixed;
            height: 100%;
            z-index: 1100;
        }
        .sidebar-header { text-align: center; margin-bottom: 2rem; }
        .logo { font-size: 1.75rem; font-weight: 800; color: var(--primary-color); text-decoration: none; }
        .sidebar-nav { flex-grow: 1; }
        .sidebar-nav ul { list-style: none; }
        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            border-radius: var(--border-radius-md);
            text-decoration: none;
            color: var(--text-light);
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }
        .sidebar-nav ul li a:hover { background: var(--primary-light); color: var(--primary-color); }
        .sidebar-nav ul li a.active { background: var(--primary-color); color: white; }
        .sidebar-nav ul li a .nav-icon { font-size: 1.2rem; width: 20px; text-align: center; }
        .sidebar-footer { text-align: center; font-size: 0.8rem; color: var(--text-light); }

        /* --- Main Content --- */
        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }

        /* --- Header --- */
        .header {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(8px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; top: 0; z-index: 1000;
            border-bottom: 1px solid var(--border-color);
        }
        .menu-toggle { font-size: 1.5rem; cursor: pointer; display: none; }
        .header-right { display: flex; align-items: center; gap: 1rem; }
        .user-profile { font-weight: 600; }

        /* --- Dashboard Content --- */
        .main-container { padding: 2rem; }
        .dashboard-header { margin-bottom: 2rem; }
        .welcome-title { font-size: 2rem; font-weight: 700; }
        .welcome-subtitle { font-size: 1rem; color: var(--text-light); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card {
            background: var(--bg-white); padding: 1.5rem; border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem;
            transition: var(--transition);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .stat-icon {
            font-size: 1.5rem; width: 50px; height: 50px; border-radius: 50%; display: grid; place-items: center;
        }
        .stat-card:nth-child(1) .stat-icon { background: hsl(215, 90%, 92%); color: hsl(215, 80%, 50%); }
        .stat-card:nth-child(2) .stat-icon { background: hsl(160, 90%, 92%); color: hsl(160, 80%, 40%); }
        .stat-card:nth-child(3) .stat-icon { background: hsl(350, 90%, 92%); color: hsl(350, 80%, 50%); }
        .stat-card:nth-child(4) .stat-icon { background: hsl(50, 90%, 92%); color: hsl(50, 80%, 50%); }
        .stat-value { font-size: 2rem; font-weight: 700; }
        .stat-label { color: var(--text-light); }
        
        .dashboard-section { background: var(--bg-white); padding: 2rem; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-sm); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-title { font-size: 1.5rem; font-weight: 600; }
        
        .btn {
            padding: 0.75rem 1.5rem; border: none; border-radius: var(--border-radius-md);
            background-color: var(--primary-color); color: white; font-weight: 600; cursor: pointer;
            transition: var(--transition); display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn:hover { background-color: var(--primary-dark); transform: translateY(-2px); }
        
        /* Video List */
        .video-list-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem; border-bottom: 1px solid var(--border-color); transition: var(--transition);
        }
        .video-list-item:last-child { border-bottom: none; }
        .video-list-item:hover { background-color: var(--bg-light); }
        .video-list-details { display: flex; align-items: center; gap: 1rem; }
        .video-list-thumbnail { width: 120px; height: 67px; border-radius: var(--border-radius-md); object-fit: cover; }
        .video-list-info h4 { margin: 0 0 0.25rem; font-weight: 600; }
        .video-list-stats { font-size: 0.9rem; color: var(--text-light); display: flex; gap: 1rem; }
        
        /* Modal */
        .modal {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%;
            overflow: auto; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: #fefefe; padding: 2rem; width: 90%; max-width: 600px;
            border-radius: var(--border-radius-lg); position: relative; animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close-btn { position: absolute; top: 1rem; right: 1.5rem; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        
        /* Form */
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 0.75rem; border-radius: var(--border-radius-md);
            border: 1px solid var(--border-color); box-sizing: border-box; font-family: inherit;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
             outline: 2px solid var(--primary-color); border-color: var(--primary-color);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
        }

    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo">TalentUp</a>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard_user.php" class="active"><i class="fas fa-th-large nav-icon"></i> Dashboard</a></li>
                <li><a href="video_list.php"><i class="fas fa-play-circle nav-icon"></i> All Videos</a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle nav-icon"></i> My Profile</a></li>
                <li><a href="#"><i class="fas fa-cog nav-icon"></i> Settings</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="#" id="logoutBtnSidebar" class="btn" style="width: 100%;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <div class="main-content" id="main-content">
        <header class="header">
            <i class="fas fa-bars menu-toggle" id="menu-toggle"></i>
            <div class="header-right">
                <span class="user-profile">Hello, <span id="username-placeholder"><?php echo htmlspecialchars($username); ?></span>!</span>
            </div>
        </header>

        <main class="main-container">
            <div class="dashboard-header">
                <h1 class="welcome-title">Your Dashboard</h1>
                <p class="welcome-subtitle">Here's a summary of your activity. Let's get your talent noticed!</p>
            </div>

            <div class="stats-grid" id="stats-grid">
                <!-- Stats will be loaded here by JavaScript -->
            </div>

            <section class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Your Recent Videos</h2>
                    <button class="btn" id="upload-video-btn"><i class="fas fa-upload"></i> Upload New Video</button>
                </div>
                <div id="video-list-container">
                    <p>Loading your videos...</p>
                </div>
            </section>
        </main>
    </div>

    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Upload a New Video</h2>
            <form id="uploadVideoForm" enctype="multipart/form-data">
                <div id="upload-message-area"></div>
                <div class="form-group">
                    <label for="videoTitle">Video Title *</label>
                    <input type="text" id="videoTitle" name="videoTitle" required>
                </div>
                <div class="form-group">
                    <label for="videoDescription">Description</label>
                    <textarea id="videoDescription" name="videoDescription" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="videoCategory">Category *</label>
                    <select id="videoCategory" name="videoCategory" required>
                        <option value="">Select a Category</option>
                        <option value="Music">Music</option>
                        <option value="Dance">Dance</option>
                        <option value="Comedy">Comedy</option>
                        <option value="Art">Art</option>
                        <option value="Sports">Sports</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="videoFile">Video File * (.mp4, .mov, .webm)</label>
                    <input type="file" id="videoFile" name="videoFile" accept="video/mp4,video/quicktime,video/webm" required>
                </div>
                 <div class="form-group">
                    <label for="thumbnailFile">Thumbnail Image * (.jpg, .png)</label>
                    <input type="file" id="thumbnailFile" name="thumbnailFile" accept="image/jpeg,image/png" required>
                </div>
                <button type="submit" class="btn" id="submitUploadBtn">Submit Video</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- ELEMENTS ---
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menu-toggle');
        const uploadBtn = document.getElementById('upload-video-btn');
        const modal = document.getElementById('uploadModal');
        const closeBtn = document.querySelector('.modal .close-btn');
        const uploadForm = document.getElementById('uploadVideoForm');
        
        // --- EVENT LISTENERS ---
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
        uploadBtn.addEventListener('click', () => modal.style.display = 'flex');
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', (event) => {
            if (event.target == modal) modal.style.display = 'none';
        });
        uploadForm.addEventListener('submit', handleUploadSubmit);
        document.getElementById('logoutBtnSidebar').addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
        
        loadDashboardData();
    });

    async function loadDashboardData() {
        try {
            const response = await fetch('php_api/user_dashboard_data.php', { credentials: 'include' });
            
            // Log the raw text response to see if it's valid JSON
            const responseText = await response.text();
            console.log("Raw response from server:", responseText);

            if (!response.ok) {
                throw new Error(`Network error: ${response.status} - ${response.statusText}`);
            }

            const data = JSON.parse(responseText);
            console.log("Parsed dashboard data:", data);


            if (data.success) {
                renderStats(data.stats);
                renderVideos(data.videos);
            } else {
                document.getElementById('stats-grid').innerHTML = `<p>Could not load dashboard data: ${data.message}</p>`;
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            document.getElementById('stats-grid').innerHTML = `<p>Could not connect to the server or failed to parse response. Check the console for more details.</p>`;
        }
    }

    function renderStats(stats) {
        const statsGrid = document.getElementById('stats-grid');
        // Use optional chaining (?.) and nullish coalescing (??) for safety
        statsGrid.innerHTML = `
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-video"></i></div>
                <div><div class="stat-value">${stats?.total_videos ?? 0}</div><div class="stat-label">Videos Uploaded</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-eye"></i></div>
                <div><div class="stat-value">${stats?.total_views ?? 0}</div><div class="stat-label">Total Views</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-heart"></i></div>
                <div><div class="stat-value">${stats?.total_likes ?? 0}</div><div class="stat-label">Total Likes</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <div><div class="stat-value">${stats?.total_comments ?? 0}</div><div class="stat-label">Comments Received</div></div>
            </div>
        `;
    }

    function renderVideos(videos) {
        const videoListContainer = document.getElementById('video-list-container');
        if (videos && videos.length > 0) {
            videoListContainer.innerHTML = videos.map(video => `
                <div class="video-list-item">
                    <div class="video-list-details">
                        <img src="${video.thumbnail_path || 'https://placehold.co/120x67/e9ecef/495057?text=No+Thumb'}" alt="${video.title}" class="video-list-thumbnail">
                        <div class="video-list-info">
                            <h4>${video.title}</h4>
                            <div class="video-list-stats">
                                <span><i class="fas fa-eye"></i> ${video.views ?? 0}</span>
                                <span><i class="fas fa-heart"></i> ${video.likes ?? 0}</span>
                                <span><i class="fas fa-comment"></i> ${video.comment_count ?? 0}</span>
                            </div>
                        </div>
                    </div>
                    <a href="video_detail.php?id=${video.id}" class="btn">View</a>
                </div>
            `).join('');
        } else {
            videoListContainer.innerHTML = '<p>You have not uploaded any videos yet. Click "Upload New Video" to get started!</p>';
        }
    }

    async function handleUploadSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = document.getElementById('submitUploadBtn');
        const messageArea = document.getElementById('upload-message-area');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        messageArea.innerHTML = '';

        try {
            const response = await fetch('php_api/upload_video.php', { method: 'POST', body: formData, credentials: 'include' });
            const data = await response.json();
            
            if (data.success) {
                messageArea.innerHTML = `<div style="color: var(--success);">${data.message}</div>`;
                form.reset();
                loadDashboardData();
                setTimeout(() => {
                    document.getElementById('uploadModal').style.display = 'none';
                    messageArea.innerHTML = '';
                }, 2000);
            } else {
                throw new Error(data.message || 'An unknown error occurred during upload.');
            }
        } catch (error) {
            console.error('Upload error:', error);
            messageArea.innerHTML = `<div style="color: var(--error);">Upload failed: ${error.message}</div>`;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit Video';
        }
    }

    async function logout() {
        try {
            await fetch('php_api/auth_logout.php', { method: 'POST', credentials: 'include' });
        } finally {
            window.location.href = 'login.php';
        }
    }
    </script>
</body>
</html>

