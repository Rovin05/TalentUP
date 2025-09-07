<?php
session_start();
require 'db_connect.php';

$errors = [];
$username = '';
$login_success = false;

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'admin':
        case 'super_admin':
            header("Location: admin_dashboard.php");
            break;
        case 'judge':
            header("Location: judge_dashboard.php");
            break;
        default:
            header("Location: user_dashboard.php");
            break;
    }
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); // username or email
    $password = trim($_POST['password']);

    // Validation for empty fields
    if (empty($username)) {
        $errors[] = "Username or Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $db_username, $db_password, $db_role);
            $stmt->fetch();

            if (password_verify($password, $db_password)) {
                // Login successful
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $db_role;

                $login_success = true;

                // Optional: set message before redirect
                $_SESSION['message'] = "Login successful! Redirecting...";

                // Redirect based on role
                switch ($db_role) {
                    case 'admin':
                    case 'super_admin':
                        header("Refresh:2; url=admin_dashboard.php");
                        break;
                    case 'judge':
                        header("Refresh:2; url=judge_dashboard.php");
                        break;
                    default:
                        header("Refresh:2; url=user_dashboard.php");
                        break;
                }
            } else {
                $errors[] = "Invalid username/email or password.";
            }
        } else {
            $errors[] = "Invalid username/email or password.";
        }

        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TalentUp SriLanka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-gray-800 p-8 md:p-12 rounded-lg shadow-xl w-full max-w-md">
            <div class="text-center mb-8">
                <a href="index.php" class="text-4xl font-bold text-white">Talent<span class="text-blue-400">Up</span> SriLanka</a>
                <p class="text-gray-400 mt-2">Welcome back! Please log in.</p>
            </div>

            <!-- Display success message -->
            <?php if ($login_success): ?>
                <div class="bg-green-500 text-white p-4 rounded-lg mb-6">
                    <p>Login successful! Redirecting to your dashboard...</p>
                </div>
            <?php endif; ?>

            <!-- Display session messages -->
            <?php if (isset($_SESSION['message']) && !$login_success): ?>
                <div class="bg-green-500 text-white p-4 rounded-lg mb-6">
                    <p><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Display errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-4">
                    <label for="username" class="block text-gray-300 mb-2">Username or Email</label>
                    <input type="text" id="username" name="username" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-300 mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg transition duration-300">Log In</button>
            </form>

            <p class="text-center text-gray-400 mt-6">
                Don't have an account? <a href="register.php" class="text-blue-400 hover:underline">Register Now</a>
            </p>
        </div>
    </div>
</body>
</html>
