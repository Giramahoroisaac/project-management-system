<?php
// Password Policy Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Login Security Configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 15 * 60); // 15 minutes in seconds

// Session Security Configuration
define('SESSION_LIFETIME', 2 * 60 * 60); // 2 hours in seconds
define('SESSION_REGENERATE_TIME', 15 * 60); // Regenerate session ID every 15 minutes

// File Upload Security Configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB in bytes
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt', 'zip']);

// Password Reset Configuration
define('PASSWORD_RESET_EXPIRE', 60 * 60); // 1 hour in seconds

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }
    
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must include at least one uppercase letter";
    }
    
    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must include at least one lowercase letter";
    }
    
    if (PASSWORD_REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must include at least one number";
    }
    
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must include at least one special character";
    }
    
    return $errors;
}

/**
 * Check if user is locked out
 */
function isUserLockedOut($conn, $email) {
    $sql = "SELECT failed_attempts, last_failed_attempt FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['failed_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $lockout_time = strtotime($row['last_failed_attempt']) + LOGIN_LOCKOUT_DURATION;
            if (time() < $lockout_time) {
                return $lockout_time;
            } else {
                // Reset failed attempts after lockout period
                resetFailedAttempts($conn, $email);
                return false;
            }
        }
    }
    return false;
}

/**
 * Increment failed login attempts
 */
function incrementFailedAttempts($conn, $email) {
    $sql = "UPDATE users SET 
            failed_attempts = COALESCE(failed_attempts, 0) + 1,
            last_failed_attempt = CURRENT_TIMESTAMP 
            WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
}

/**
 * Reset failed login attempts
 */
function resetFailedAttempts($conn, $email) {
    $sql = "UPDATE users SET failed_attempts = 0 WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
}

/**
 * Generate secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate file upload
 */
function validateFileUpload($file) {
    $errors = [];
    
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = "File size exceeds maximum limit of " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_FILE_TYPES)) {
        $errors[] = "File type not allowed. Allowed types: " . implode(', ', ALLOWED_FILE_TYPES);
    }
    
    return $errors;
}
?>
