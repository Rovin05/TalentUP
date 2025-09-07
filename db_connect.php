<?php
// File: db_connect.php
// This file contains the database connection configuration.

// --- Database Configuration ---
$servername = "localhost"; // Your database server name (usually 'localhost' for XAMPP)
$username = "root";        // Your database username (default for XAMPP is 'root')
$password = "";            // Your database password (default for XAMPP is empty)
$dbname = "talentup_db";   // Your database name

// --- Create Connection ---
// Create a new mysqli object to connect to the database.
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Check Connection ---
// Check if the connection was successful. If not, terminate the script and show an error.
if ($conn->connect_error) {
    // The die() function prints a message and exits the current script.
    die("Connection failed: " . $conn->connect_error);
}
?>
