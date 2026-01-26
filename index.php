<?php
session_start();
require_once 'config/database.php';
require_once 'includes/session.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

if (isAdmin()) {
    header('Location: admin/index.php');
} else {
    header('Location: student/index.php');
}
exit();
?>