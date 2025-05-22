<?php
require_once 'config/db_connect.php';
require_once 'includes/password_reset.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if ($email) {
        $resetHandler = new PasswordReset($conn);
        $resetHandler->createResetToken($email);
    }
    
    // Always redirect to prevent email enumeration
    header('Location: reset_password.php?sent=1');
    exit;
}

// If accessed directly without POST, redirect to the form
header('Location: reset_password.php');
exit;
