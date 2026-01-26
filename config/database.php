<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');  // Use 127.0.0.1 instead of localhost
define('DB_PORT', '3306');       // Explicitly specify port
define('DB_USER', 'kiyoonewton'); // Your Docker MySQL user
define('DB_PASS', 'Olaoluwa@41'); // Your Docker MySQL password
define('DB_NAME', 'exam_system');

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to utf8mb4 for better character support
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Get database connection
$conn = getDBConnection();
?>