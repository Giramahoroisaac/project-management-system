<?php
// Security Configuration Settings

// Password Policy Settings
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Login Security Settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 15); // minutes
define('PASSWORD_RESET_EXPIRY', 60); // minutes

// Session Security Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('REQUIRE_SSL', true);

// File Upload Security Settings
define('ALLOWED_FILE_TYPES', [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
]);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Two-Factor Authentication Settings
define('ENABLE_2FA', true);
define('2FA_ISSUER', 'Doc Project System');
?>
