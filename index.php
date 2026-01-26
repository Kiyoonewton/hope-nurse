<?php
require_once 'includes/session.php';

// Redirect based on login status and role
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
    } else {
        header("Location: student/index.php");
    }
} else {
    header("Location: auth/login.php");
}
exit();
?>