<?php
session_start();
require_once 'auth.php';
require_once '../config/db_connect.php';

if (isLoggedIn()) {
    // Log the logout action
    logAction($conn, 'logout', 'User logged out');
    
    // Destroy session
    session_destroy();
}

// Redirect to login page
header("Location: ../login.php");
exit();
?>
