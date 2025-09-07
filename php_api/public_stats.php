<?php
require 'db_connect.php';

// In a real app, you would have tables for videos, judges, and competitions.
// For now, we will use the user count and hardcode the rest.
$user_count_query = "SELECT COUNT(*) as total FROM users";
$user_result = $conn->query($user_count_query);
$user_count = $user_result->fetch_assoc()['total'];

$stats = [
    'users' => $user_count . '+',
    'videos' => '1,200+', // Placeholder
    'judges' => '25+',     // Placeholder
    'competitions' => '12+'  // Placeholder
];

echo json_encode(['success' => true, 'stats' => $stats]);
$conn->close();
?>