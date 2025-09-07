<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
// Fetch all users except the admin currently logged in
$stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users WHERE id != ? ORDER BY created_at DESC");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

include 'header.php';
?>
<title>Manage Users - Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<main class="flex-grow container mx-auto px-6 py-12">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center mb-8">
            <a href="admin_dashboard.php" class="text-gray-400 hover:text-white mr-4">&larr; Back to Dashboard</a>
            <h1 class="text-3xl md:text-4xl font-bold">Manage Users</h1>
        </div>
        <div class="bg-gray-800/50 p-4 sm:p-8 rounded-lg shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="p-4">Username</th>
                            <th class="p-4 hidden md:table-cell">Email</th>
                            <th class="p-4">Role</th>
                            <th class="p-4 hidden sm:table-cell">Joined</th>
                            <th class="p-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr id="user-row-<?php echo $user['id']; ?>" class="border-b border-gray-700 hover:bg-gray-800">
                            <td class="p-4 font-semibold"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="p-4 text-gray-400 hidden md:table-cell"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="p-4">
                                <select onchange="updateRole(<?php echo $user['id']; ?>, this.value)" class="bg-gray-700 border-gray-600 rounded p-2">
                                    <option value="user" <?php if($user['role'] == 'user') echo 'selected'; ?>>User</option>
                                    <option value="judge" <?php if($user['role'] == 'judge') echo 'selected'; ?>>Judge</option>
                                    <option value="admin" <?php if($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                </select>
                            </td>
                            <td class="p-4 text-gray-400 hidden sm:table-cell"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="p-4">
                                <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')" class="text-red-500 hover:text-red-400"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal and JS for actions -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
    <div class="bg-gray-800 rounded-lg p-8 w-full max-w-md mx-4">
        <h3 class="text-2xl font-bold mb-4">Are you sure?</h3>
        <p class="text-gray-400 mb-6">You are about to delete the user <strong id="delete-username"></strong>. This will remove all their videos, comments, and votes. This action cannot be undone.</p>
        <div class="flex justify-end space-x-4">
            <button id="cancel-delete" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg">Cancel</button>
            <button id="confirm-delete-btn" class="bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-4 rounded-lg">Delete User</button>
        </div>
    </div>
</div>

<script>
function updateRole(userId, newRole) {
    const formData = new FormData();
    formData.append('action', 'change_role');
    formData.append('user_id', userId);
    formData.append('new_role', newRole);

    fetch('handle_admin_actions.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.message);
            location.reload(); // Reload to reset dropdown on failure
        }
    });
}

function confirmDelete(userId, username) {
    const modal = document.getElementById('delete-modal');
    document.getElementById('delete-username').textContent = username;
    modal.classList.remove('hidden');
    
    document.getElementById('cancel-delete').onclick = () => modal.classList.add('hidden');
    document.getElementById('confirm-delete-btn').onclick = () => {
        deleteUser(userId);
        modal.classList.add('hidden');
    };
}

function deleteUser(userId) {
    const formData = new FormData();
    formData.append('action', 'delete_user');
    formData.append('user_id', userId);

    fetch('handle_admin_actions.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('user-row-' + userId);
            if(row) row.remove();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
</body>
</html>
