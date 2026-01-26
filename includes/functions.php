<?php
// Common utility functions

// Sanitize input to prevent XSS
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

// Calculate percentage
function calculatePercentage($obtained, $total) {
    if ($total == 0) return 0;
    return round(($obtained / $total) * 100, 2);
}

// Get exam status badge class
function getExamStatusBadge($status) {
    $badges = [
        'draft' => 'badge-secondary',
        'active' => 'badge-success',
        'closed' => 'badge-danger'
    ];
    return $badges[$status] ?? 'badge-secondary';
}

// Get attempt status badge class
function getAttemptStatusBadge($status) {
    $badges = [
        'not_started' => 'badge-info',
        'in_progress' => 'badge-warning',
        'submitted' => 'badge-success',
        'expired' => 'badge-danger'
    ];
    return $badges[$status] ?? 'badge-secondary';
}

// Format duration (minutes to hours and minutes)
function formatDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' min' . ($minutes != 1 ? 's' : '');
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . ' hr' . ($hours != 1 ? 's' : '') . ($mins > 0 ? ' ' . $mins . ' min' . ($mins != 1 ? 's' : '') : '');
}

// Set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'info';
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

// Display flash message HTML
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ][$flash['type']] ?? 'alert-info';
        
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        echo '<span aria-hidden="true">&times;</span>';
        echo '</button>';
        echo '</div>';
    }
}

// Redirect with message
function redirectWithMessage($url, $type, $message) {
    setFlashMessage($type, $message);
    header("Location: " . $url);
    exit();
}

// Get question type display name
function getQuestionTypeDisplay($type) {
    $types = [
        'multiple_choice' => 'Multiple Choice',
        'multiple_select' => 'Multiple Select',
        'true_false' => 'True/False',
        'short_answer' => 'Short Answer',
        'fill_blank' => 'Fill in the Blank'
    ];
    return $types[$type] ?? $type;
}

// Generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}
?>