<?php
session_start();
require 'db_connect.php';

$errors = [];
$username = '';
$email = '';
$age_group = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $age_group = $_POST['age_group'];

    if (empty($username)) { $errors[] = "Username is required"; }
    if (empty($email)) { $errors[] = "Email is required"; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email format"; }
    if (empty($password)) { $errors[] = "Password is required"; }
    if (strlen($password) < 6) { $errors[] = "Password must be at least 6 characters long"; }
    if ($password !== $password_confirm) { $errors[] = "Passwords do not match"; }
    if (empty($age_group)) { $errors[] = "Age group is required"; }

    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username or email already taken";
        }
        $stmt->close();
    }

    // If no errors, insert new user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // By default, role is 'user' as per your database structure
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, age_group) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $age_group);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Registration successful! You can now log in.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
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
    <title>Register - TalentUp SriLanka</title>
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
                <p class="text-gray-400 mt-2">Create your account to showcase your talent</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="mb-4">
                    <label for="username" class="block text-gray-300 mb-2">Username</label>
                    <input type="text" id="username" name="username" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-300 mb-2">Email</label>
                    <input type="email" id="email" name="email" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                 <div class="mb-4">
                    <label for="age_group" class="block text-gray-300 mb-2">Age Group</label>
                    <select id="age_group" name="age_group" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="" disabled <?php if(empty($age_group)) echo 'selected'; ?>>Select your age group</option>
                        <option value="10-15" <?php if($age_group == '10-15') echo 'selected'; ?>>10-15 Years</option>
                        <option value="16-20" <?php if($age_group == '16-20') echo 'selected'; ?>>16-20 Years</option>
                        <option value="21-30" <?php if($age_group == '21-30') echo 'selected'; ?>>21-30 Years</option>
                        <option value="30+" <?php if($age_group == '30+') echo 'selected'; ?>>30+ Years</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-300 mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-6">
                    <label for="password_confirm" class="block text-gray-300 mb-2">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg transition duration-300">Register</button>
            </form>
            <p class="text-center text-gray-400 mt-6">
                Already have an account? <a href="login.php" class="text-blue-400 hover:underline">Log In</a>
            </p>
        </div>
    </div>
</body>
</html>
