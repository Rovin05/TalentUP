<?php
include 'header.php';
require 'db_connect.php';

// Fetch all users who have the role of 'judge'
$sql = "SELECT username, created_at FROM users WHERE role = 'judge' ORDER BY username ASC";
$result = $conn->query($sql);

$judges = [];
if ($result && $result->num_rows > 0) {
    $judges = $result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>
<title>Our Judges - TalentUp SriLanka</title>

<main class="flex-grow container mx-auto px-6 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold">Meet the Judges</h1>
        <p class="text-lg text-gray-400 mt-2">The esteemed panel of experts who will be rating the performances.</p>
    </div>

    <?php if (empty($judges)): ?>
        <div class="text-center bg-gray-800 p-12 rounded-lg">
            <h2 class="text-2xl font-semibold text-gray-300">Judges Coming Soon!</h2>
            <p class="text-gray-400 mt-2">We are in the process of finalizing our expert panel. Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8 max-w-6xl mx-auto">
            <?php foreach ($judges as $judge): ?>
                <div class="bg-gray-800 rounded-lg p-6 text-center shadow-lg transform hover:scale-105 hover:shadow-2xl transition-all duration-300">
                    <img class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-gray-600" 
                         src="https://placehold.co/200x200/4a5568/FFFFFF?text=<?php echo strtoupper(substr($judge['username'], 0, 1)); ?>" 
                         alt="Profile picture of <?php echo htmlspecialchars($judge['username']); ?>">
                    <h3 class="text-xl font-bold text-white mb-1"><?php echo htmlspecialchars($judge['username']); ?></h3>
                    <p class="text-blue-400 font-semibold mb-3">Official Judge</p>
                    <p class="text-gray-400 text-sm">
                        With a keen eye for talent, <?php echo htmlspecialchars($judge['username']); ?> brings years of experience to the panel.
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
