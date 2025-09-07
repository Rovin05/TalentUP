<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #111827; color: #d1d5db; }
        .bg-video-dark { background-color: #0c111a; }
        .aspect-w-16 { position: relative; padding-bottom: 56.25%; }
        .aspect-h-9 { }
        .aspect-w-16 > * { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
    </style>
</head>
<body class="flex flex-col min-h-screen">

<header class="bg-gray-800/80 backdrop-blur-sm shadow-lg sticky top-0 z-40">
    <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="text-2xl font-bold text-white">TalentUp <span class="text-blue-400">SriLanka</span></a>
        <ul class="hidden md:flex items-center space-x-6">
            <li><a href="index.php" class="text-gray-300 hover:text-white">Home</a></li>
            <li><a href="videos.php" class="text-gray-300 hover:text-white">Videos</a></li>
            <li><a href="judges.php" class="text-gray-300 hover:text-white">Judges</a></li>
            <li><a href="upload.php" class="text-gray-300 hover:text-white">Upload</a></li>
        </ul>
        <div class="flex items-center space-x-4">
             <?php if (isset($_SESSION['user_id'])): ?>
                <div class="relative group">
                    <button class="flex items-center space-x-2 focus:outline-none">
                        <span class="font-semibold text-white hidden sm:inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center font-bold text-lg">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                    </button>
                     <div class="absolute right-0 mt-2 w-48 bg-gray-700 rounded-lg shadow-xl py-2 opacity-0 group-hover:opacity-100 invisible group-hover:visible transition-all duration-300 transform-gpu scale-95 group-hover:scale-100">
                        <?php 
                            $dashboard_link = 'user_dashboard.php';
                            if ($_SESSION['role'] === 'judge') $dashboard_link = 'judge_dashboard.php';
                            if ($_SESSION['role'] === 'admin') $dashboard_link = 'admin_dashboard.php';
                        ?>
                        <a href="<?php echo $dashboard_link; ?>" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">My Dashboard</a>
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">Manage Profile</a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="hidden sm:inline-block bg-transparent border border-blue-500 text-blue-400 hover:bg-blue-500 hover:text-white font-semibold py-2 px-4 rounded-lg transition">Login</a>
                <a href="register.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

