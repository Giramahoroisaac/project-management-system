<?php
// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the VERY TOP (no output before this)
session_start();

// Use absolute path for config and includes
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    error_log("Login attempt for email: $email");
    
    // Fetch user
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Debug information
    error_log("Login attempt details:");
    error_log("Email: " . $email);
    error_log("User found: " . ($row ? "Yes" : "No"));
    if ($row) {
        error_log("Stored hash: " . $row['password']);
        error_log("Role: " . $row['role']);
        error_log("Status: " . $row['status']);
    }

    if ($row) {
        error_log("User found: Role={$row['role']}, Status={$row['status']}");

        // Verify password
        if (password_verify($password, $row['password'])) {
            error_log("Password valid");

            // Check account status
            if ($row['status'] == 'pending') {
                $_SESSION['error'] = "Account pending approval.";
                header("Location: /doc_project/login.php");
                exit();
            } elseif ($row['status'] == 'disabled') {
                $_SESSION['error'] = "Account disabled. Contact admin.";
                header("Location: /doc_project/login.php");
                exit();
            }

            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            // Set user session data
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['auth_time'] = time();
            $_SESSION['last_activity'] = time();

            // Log the successful login
            logAction($conn, 'login', "User {$row['email']} logged in as {$row['role']}");

            // Clear any error messages
            unset($_SESSION['error']);

            // Redirect based on role
            if ($row['role'] === 'super_admin' || $row['role'] === 'sub_admin') {
                header("Location: /doc_project/admin/dashboard.php");
            } else {
                header("Location: /doc_project/user/dashboard.php");
            }
            exit();
        } else {
            error_log("Invalid password for email: $email");
            $_SESSION['error'] = "Invalid credentials"; // Generic message for security
        }
    } else {
        error_log("Email not found: $email");
        $_SESSION['error'] = "Invalid credentials"; // Don't reveal if email exists
    }
    
    // Failed login fallback
    header("Location: /doc_project/login.php");
    exit();
} else {
    // If not POST request, redirect to login page
    header("Location: /doc_project/login.php");
    exit();
}

// If directly accessed, redirect to login
header("Location: /doc_project/login.php");
exit();