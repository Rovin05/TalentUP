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
    <title>Discover Videos - TalentUp Sri Lanka</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-hue: 215;
            --secondary-hue: 170;
            --primary-color: hsl(var(--primary-hue), 78%, 51%);
            --secondary-color: hsl(var(--secondary-hue), 85%, 41%);
            --accent-color: #F97316;
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
        .shape1 { width: 40vw; height: 40vw; max-width: 500px; max-height: 500px; top: -10vh; left: -10vw; animation-duration: 25s; }
        .shape2 { width: 50vw; height: 50vw; max-width: 700px; max-height: 700px; bottom: -20vh; right: -20vw; animation-duration: 30s; }
        @keyframes float {
            from { transform: translateY(0px) rotate(0deg); }
            to { transform: translateY(-20px) rotate(90deg); }
        }

        /* --- Header & Navigation (Consistent with index.php) --- */
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
        .nav-links { display: flex; list-style: none; gap: 0.5rem; align-items: center; }
        .nav-links > li > a {
            color: var(--text-dark); text-decoration: none; font-weight: 500;
            transition: var(--transition); position: relative; padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        .nav-links > li > a:hover { color: var(--primary-color); background-color: hsl(var(--primary-hue), 78%, 95%); }
        .nav-links > li > a.active { color: var(--primary-color); font-weight: 700; background-color: hsl(var(--primary-hue), 78%, 95%);}
        .nav-right { display: flex; align-items: center; gap: 1rem; }
        
        .user-menu-container { position: relative; }
        .user-menu-button {
            display: flex; align-items: center; gap: 0.5rem; background: transparent; border: none; cursor: pointer;
            font-family: inherit; font-size: 1rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 8px; transition: var(--transition);
        }
        .user-menu-button:hover { background-color: hsl(var(--primary-hue), 78%, 95%); color: var(--primary-color); }
        .user-menu-button .fa-user-circle { font-size: 1.5rem; }
        .user-menu-button .fa-chevron-down { font-size: 0.8rem; transition: var(--transition); margin-left: 0.25rem; }
        .user-menu-container.active .user-menu-button .fa-chevron-down { transform: rotate(180deg); }
        .user-menu-dropdown {
            position: absolute; top: calc(100% + 10px); right: 0; background: var(--bg-white); border-radius: 8px;
            box-shadow: var(--shadow-lg); min-width: 220px; padding: 0.5rem; z-index: 1010;
            opacity: 0; visibility: hidden; transform: translateY(10px); transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
        }
        .user-menu-container.active .user-menu-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .user-menu-dropdown a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; text-decoration: none; color: var(--text-dark); border-radius: 6px; transition: var(--transition); }
        .user-menu-dropdown a:hover { background-color: hsl(var(--primary-hue), 78%, 95%); color: var(--primary-color); }
        .user-menu-dropdown .dropdown-divider { height: 1px; background-color: var(--border-color); margin: 0.5rem 0; }
        .user-menu-dropdown .fa-fw { width: 20px; text-align: center; }

        .hamburger { display: none; cursor: pointer; flex-direction: column; gap: 5px; }
        .hamburger .bar { width: 25px; height: 3px; background-color: var(--text-dark); border-radius: 5px; transition: var(--transition); }
        .mobile-nav { position: fixed; top: 68px; left: -100%; width: 100%; height: calc(100vh - 68px); background: var(--bg-white); display: flex; flex-direction: column; align-items: center; padding-top: 2rem; transition: left 0.4s ease-in-out; z-index: 999; }
        .mobile-nav.active { left: 0; }

        /* --- Main Content --- */
        .main-container { max-width: 1400px; margin: 2rem auto; padding: 0 5%; }
        .page-header { text-align: center; margin-bottom: 2.5rem; }
        .page-title { font-size: 2.8rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-dark); }
        .page-subtitle { font-size: 1.2rem; color: var(--text-light); max-width: 600px; margin: 0 auto; }

        /* Filters */
        .filters-section { background: white; border-radius: 12px; box-shadow: var(--shadow-sm); padding: 1.5rem; margin-bottom: 2rem; }
        .filters-grid { display: grid; grid-template-columns: 1fr; md:grid-template-columns: repeat(3, 1fr); gap: 1rem; align-items: end; }
        @media (min-width: 768px) { .filters-grid { grid-template-columns: 2fr 1fr 1fr; } }
        .filter-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; }
        .filter-select, .search-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: var(--transition); }
        .filter-select:focus, .search-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px hsla(var(--primary-hue), 78%, 51%, 0.2); }

        /* Video Grid */
        .videos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .video-card {
            background: white; border-radius: 12px; box-shadow: var(--shadow-sm);
            overflow: hidden; transition: var(--transition); display: flex; flex-direction: column;
        }
        .video-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
        .video-thumbnail { position: relative; padding-top: 56.25%; /* 16:9 Aspect Ratio */ }
        .video-thumbnail img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .video-content { padding: 1rem; flex-grow: 1; display: flex; flex-direction: column; }
        .video-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .video-title a { text-decoration: none; color: inherit; }
        .video-meta { display: flex; justify-content: space-between; color: var(--text-light); font-size: 0.9rem; margin-bottom: 1rem; }
        .video-actions { margin-top: auto; display: flex; gap: 0.5rem; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; transition: var(--transition); border: 2px solid transparent; }
        .btn-primary { background: var(--primary-color); color: white; flex: 1; text-align: center; }
        .btn-primary:hover { background: var(--primary-dark); }
        .like-btn { background: var(--border-color); color: var(--text-light); border: none; }
        .like-btn.liked { background: #FEE2E2; color: #DC2626; }
        
        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 2.5rem; }
        .pagination-btn {
            width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; background: white; color: var(--text-dark); font-weight: 600; text-decoration: none;
            box-shadow: var(--shadow-sm); transition: var(--transition); border: none; cursor: pointer;
        }
        .pagination-btn:hover, .pagination-btn.active { background: var(--primary-color); color: white; transform: translateY(-2px); }
        .pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .empty-state, .loading-state { grid-column: 1 / -1; text-align: center; padding: 4rem 1rem; background: white; border-radius: 12px; box-shadow: var(--shadow-sm); }
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
            <ul class="nav-links">
                <!-- Populated by JS -->
            </ul>
            <div class="nav-right">
                <div class="language-selector">
                    <select id="languageSelect" disabled>
                        <option value="en">English</option>
                        <option value="si">සිංහල</option>
                        <option value="ta">தமிழ்</option>
                    </select>
                </div>
                <div class="hamburger" id="hamburger-menu">
                    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
                </div>
            </div>
        </nav>
         <div class="mobile-nav" id="mobile-menu"><!-- Populated by JS --></div>
    </header>

    <main class="main-container">
        <div class="page-header">
            <h1 class="page-title">Discover Amazing Talent</h1>
            <p class="page-subtitle">Browse our collection of exceptional talent videos from across Sri Lanka.</p>
        </div>

        <section class="filters-section">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="searchInput">Search Videos</label>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by title, uploader...">
                </div>
                <div class="filter-group">
                    <label for="categoryFilter">Category</label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">All Categories</option>
                        <option value="Music">Music</option>
                        <option value="Dance">Dance</option>
                        <option value="Comedy">Comedy</option>
                        <option value="Art">Art</option>
                        <option value="Sports">Sports</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="sortFilter">Sort By</label>
                    <select id="sortFilter" class="filter-select">
                        <option value="newest">Newest First</option>
                        <option value="views">Most Viewed</option>
                        <option value="likes">Most Liked</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="videos-grid" id="videosGrid"></section>

        <div class="pagination" id="pagination"></div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- EVENT LISTENERS ---
        document.getElementById('searchInput').addEventListener('input', () => debounce(loadVideos));
        document.getElementById('categoryFilter').addEventListener('change', () => loadVideos(1));
        document.getElementById('sortFilter').addEventListener('change', () => loadVideos(1));
        
        init();
    });

    let searchTimeout;
    function debounce(func, delay = 500) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => func(1), delay);
    }

    async function loadVideos(page = 1) {
        const videosGrid = document.getElementById('videosGrid');
        const params = new URLSearchParams({
            page,
            search: document.getElementById('searchInput').value,
            category: document.getElementById('categoryFilter').value,
            sortBy: document.getElementById('sortFilter').value
        });
        
        videosGrid.innerHTML = '<div class="loading-state"><h3>Loading videos...</h3></div>';

        try {
            const response = await fetch(`php_api/get_videos.php?${params}`, { credentials: 'include' });
            if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
            const data = await response.json();
            if (data.success) {
                renderVideoCards(data.videos);
                renderPagination(data.pagination);
            } else {
                throw new Error(data.message || 'Failed to load videos.');
            }
        } catch (error) {
            console.error('Error loading videos:', error);
            videosGrid.innerHTML = '<div class="empty-state"><h3>Could not load videos. Please try again later.</h3></div>';
        }
    }

    function renderVideoCards(videos) {
        const videosGrid = document.getElementById('videosGrid');
        if (!videos || videos.length === 0) {
            videosGrid.innerHTML = `<div class="empty-state"><h3>No Videos Found</h3><p>Try adjusting your search or filters.</p></div>`;
            return;
        }
        videosGrid.innerHTML = videos.map(video => {
            const thumbnailUrl = video.thumbnail_path || `https://placehold.co/400x225/E5E7EB/4B5563?text=${encodeURIComponent(video.title)}`;
            return `
                <div class="video-card">
                    <a href="video_detail.php?id=${video.id}" class="video-thumbnail">
                        <img src="${thumbnailUrl}" alt="${video.title}" onerror="this.src='https://placehold.co/400x225/E5E7EB/4B5563?text=Error';">
                    </a>
                    <div class="video-content">
                        <h3 class="video-title"><a href="video_detail.php?id=${video.id}">${video.title}</a></h3>
                        <div class="video-meta">
                            <span>by ${video.uploader_name}</span>
                            <div class="video-stats">
                                <span><i class="fas fa-eye"></i> ${video.views}</span>
                                <span id="like-count-${video.id}"><i class="fas fa-heart"></i> ${video.likes}</span>
                            </div>
                        </div>
                        <div class="video-actions">
                            <a href="video_detail.php?id=${video.id}" class="btn btn-primary">Watch</a>
                            <button class="btn like-btn ${video.user_has_liked ? 'liked' : ''}" onclick="toggleLike(event, ${video.id})">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
        }).join('');
    }
    
    function renderPagination(pagination) {
        const paginationContainer = document.getElementById('pagination');
        if (!pagination || pagination.total_pages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        let buttons = `<button class="pagination-btn" onclick="loadVideos(${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
        for (let i = 1; i <= pagination.total_pages; i++) {
            buttons += `<button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" onclick="loadVideos(${i})">${i}</button>`;
        }
        buttons += `<button class="pagination-btn" onclick="loadVideos(${pagination.current_page + 1})" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
        paginationContainer.innerHTML = buttons;
    }
    
    async function toggleLike(event, videoId) {
        event.stopPropagation();
        const button = event.currentTarget;
        try {
            const response = await fetch('php_api/toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId }),
                credentials: 'include'
            });
            const data = await response.json();
            if (data.success) {
                button.classList.toggle('liked', data.user_has_liked);
                document.getElementById(`like-count-${videoId}`).innerHTML = `<i class="fas fa-heart"></i> ${data.new_like_count}`;
            } else if (data.message.includes('log in')) {
                window.location.href = `login.php?redirect_url=${encodeURIComponent(window.location.pathname + window.location.search)}`;
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        }
    }

    // --- NAVIGATION LOGIC (from index.php for consistency) ---
    const updateNavigation = async () => {
        const desktopNavContainer = document.querySelector('.nav-links');
        const mobileMenu = document.getElementById('mobile-menu');
        try {
            const response = await fetch('php_api/check_session.php');
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            
            let navLinks = '';
            if (data.loggedIn && data.user) {
                const user = data.user;
                let adminLinks = (user.role === 'super_admin' || user.role === 'admin') ? `<a href="admin_panel.php"><i class="fas fa-cog fa-fw"></i> Admin Panel</a>` : '';
                if (user.role === 'super_admin' || user.role === 'admin' || user.role === 'judge') {
                    adminLinks += `<a href="judge_panel.php"><i class="fas fa-gavel fa-fw"></i> Judge Panel</a>`;
                }
                
                navLinks = `
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="video_list.php" class="active"><i class="fas fa-play-circle"></i> Videos</a></li>
                    <li class="user-menu-container">
                        <button class="user-menu-button">
                            <i class="fas fa-user-circle"></i>
                            <span>${user.username}</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-menu-dropdown">
                            <a href="dashboard_user.php"><i class="fas fa-th-large fa-fw"></i> Dashboard</a>
                            <a href="profile.php"><i class="fas fa-user fa-fw"></i> My Profile</a>
                            ${adminLinks ? '<div class="dropdown-divider"></div>' + adminLinks : ''}
                            <div class="dropdown-divider"></div>
                            <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a>
                        </div>
                    </li>`;
                
                let mobileNavLinks = `
                    <li><a href="index.php">Home</a></li>
                    <li><a href="video_list.php">Videos</a></li>
                    <li><a href="dashboard_user.php">Dashboard</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    ${(user.role === 'super_admin' || user.role === 'admin') ? `<li><a href="admin_panel.php">Admin Panel</a></li>` : ''}
                    ${(user.role === 'super_admin' || user.role === 'admin' || user.role === 'judge') ? `<li><a href="judge_panel.php">Judge Panel</a></li>` : ''}
                    <li><a href="#" id="logoutBtnMobile">Logout</a></li>`;
                mobileMenu.innerHTML = `<ul>${mobileNavLinks}</ul>`;

            } else {
                navLinks = `
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="video_list.php" class="active"><i class="fas fa-play-circle"></i> Videos</a></li>
                    <li><a href="index.php#about"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php" class="btn btn-primary" style="color:white;"><i class="fas fa-user-plus"></i> Register</a></li>`;
                mobileMenu.innerHTML = `<ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="video_list.php">Videos</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>`;
            }
            desktopNavContainer.innerHTML = navLinks;
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

    const init = async () => {
        document.getElementById('hamburger-menu').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('active');
        });
        await updateNavigation();
        await loadVideos();
    };
    </script>
</body>
</html>
