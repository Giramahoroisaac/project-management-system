<?php
require_once 'config/db_connect.php';
require_once 'includes/password_reset.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
$confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_UNSAFE_RAW);

// Validate input
if (!$token || !$password || !$confirm_password) {
    header('Location: set_new_password.php?token=' . urlencode($token) . '&error=missing');
    exit;
}

if ($password !== $confirm_password) {
    header('Location: set_new_password.php?token=' . urlencode($token) . '&error=mismatch');
    exit;
}

$resetHandler = new PasswordReset($conn);

if ($resetHandler->resetPassword($token, $password)) {
    // Password successfully reset
    header('Location: login.php?reset=success');
    exit;
} else {
    // Reset failed
    header('Location: set_new_password.php?token=' . urlencode($token) . '&error=failed');
    exit;
}
