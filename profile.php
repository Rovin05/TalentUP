<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$update_errors = [];
$pass_errors = [];
$update_success = '';
$pass_success = '';

// --- Handle Profile Details Update ---
if (isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if (empty($username) || empty($email)) {
        $update_errors[] = "Username and Email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_errors[] = "Invalid email format.";
    } else {
        // Check if username or email is taken by ANOTHER user
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $update_errors[] = "Username or Email is already taken by another account.";
        }
    }

    if (empty($update_errors)) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        if ($stmt->execute()) {
            $_SESSION['username'] = $username; // Update session username
            $update_success = "Profile updated successfully!";
        } else {
            $update_errors[] = "Failed to update profile. Please try again.";
        }
    }
}

// --- Handle Password Change ---
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_new_password'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $pass_errors[] = "All password fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $pass_errors[] = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $pass_errors[] = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($current_pass, $user['password'])) {
            // Hash new password and update
            $new_pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_pass_hashed, $user_id);
            if ($update_stmt->execute()) {
                $pass_success = "Password changed successfully!";
            } else {
                $pass_errors[] = "Failed to update password.";
            }
        } else {
            $pass_errors[] = "Incorrect current password.";
        }
    }
}

// Fetch current user data to pre-fill the form
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

include 'header.php';
?>
<title>Manage Profile - TalentUp SriLanka</title>

<main class="flex-grow container mx-auto px-6 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl md:text-4xl font-bold mb-8">Manage Your Profile</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Profile Details Form -->
            <div class="bg-gray-800 p-8 rounded-lg shadow-xl">
                <h2 class="text-2xl font-semibold mb-6">Profile Details</h2>
                <?php if ($update_success): ?>
                    <div class="bg-green-500 text-white p-3 rounded-lg mb-4"><?php echo $update_success; ?></div>
                <?php endif; ?>
                <?php foreach ($update_errors as $error): ?>
                    <div class="bg-red-500 text-white p-3 rounded-lg mb-4"><?php echo $error; ?></div>
                <?php endforeach; ?>
                <form action="profile.php" method="POST">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-300 mb-2">Username</label>
                        <input type="text" name="username" id="username" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    <div class="mb-6">
                        <label for="email" class="block text-gray-300 mb-2">Email Address</label>
                        <input type="email" name="email" id="email" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Save Changes</button>
                </form>
            </div>
            <!-- Change Password Form -->
            <div class="bg-gray-800 p-8 rounded-lg shadow-xl">
                <h2 class="text-2xl font-semibold mb-6">Change Password</h2>
                 <?php if ($pass_success): ?>
                    <div class="bg-green-500 text-white p-3 rounded-lg mb-4"><?php echo $pass_success; ?></div>
                <?php endif; ?>
                <?php foreach ($pass_errors as $error): ?>
                    <div class="bg-red-500 text-white p-3 rounded-lg mb-4"><?php echo $error; ?></div>
                <?php endforeach; ?>
                <form action="profile.php" method="POST">
                    <div class="mb-4">
                        <label for="current_password" class="block text-gray-300 mb-2">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block text-gray-300 mb-2">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2" required>
                    </div>
                    <div class="mb-6">
                        <label for="confirm_new_password" class="block text-gray-300 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" id="confirm_new_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2" required>
                    </div>
                    <button type="submit" name="change_password" class="w-full bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</main>
</body>
</html>
