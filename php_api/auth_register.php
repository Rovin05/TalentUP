<?php
// Set the content type to JSON immediately to ensure consistency.
header('Content-Type: application/json');

// Use a try-catch block to handle any critical errors (like DB connection issues).
try {
    // Include the database connection file.
    // If this file fails, the catch block will handle it gracefully.
    require 'db_connect.php';

    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    // Get the raw POST data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Check if JSON decoding was successful
    if ($data === null) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
        exit;
    }
    
    // --- Server-Side Validation ---
    $errors = [];
    if (empty($data['username']) || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $data['username'])) {
        $errors[] = 'Username must be 3-30 characters and contain only letters, numbers, and underscores.';
    }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (empty($data['password']) || strlen($data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($data['password'] !== $data['confirmPassword']) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($data['ageGroup'])) {
        $errors[] = 'Please select an age group.';
    }
    if (empty($data['userRole']) || !in_array($data['userRole'], ['user', 'judge'])) {
        $errors[] = 'Invalid user role selected.';
    }

    // If there are validation errors, send them back
    if (!empty($errors)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    // --- Check for Duplicates ---
    $username = $data['username'];
    $email = $data['email'];

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'This username is already taken. Please choose another.']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please login.']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();


    // --- Hash Password ---
    // Use BCRYPT, which is a strong and secure hashing algorithm.
    $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);


    // --- Insert User into Database ---
    $age_group = $data['ageGroup'];
    $role = $data['userRole'];

    $sql = "INSERT INTO users (username, email, password, age_group, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // This indicates an error with the SQL query itself
        throw new Exception('Database error: Could not prepare statement.');
    }

    $stmt->bind_param("sssss", $username, $email, $hashed_password, $age_group, $role);

    if ($stmt->execute()) {
        // Success
        http_response_code(201); // Created
        echo json_encode(['success' => true, 'message' => 'Registration successful! You will now be redirected.']);
    } else {
        // Failure
        throw new Exception('Registration failed during execution.');
    }

    $stmt->close();
    $conn->close();

} catch (Throwable $e) {
    // This block catches any error or exception from the 'try' block.
    http_response_code(500); // Internal Server Error
    
    // Log the detailed error to the server's error log for debugging.
    // This keeps sensitive details off the client side.
    error_log($e->getMessage());
    
    // Send a generic, safe, and valid JSON error message to the client.
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
}
?>

