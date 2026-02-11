<?php
// Database Configuration
// Use environment variables if available (for Docker), otherwise use defaults
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'kiyoonewton');
define('DB_PASS', getenv('DB_PASS') ?: 'Olaoluwa@41');
define('DB_NAME', getenv('DB_NAME') ?: 'exam_system');

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