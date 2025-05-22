<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Set New Password</h2>
            <?php
            require_once 'config/security_config.php';
            require_once 'includes/password_reset.php';
            require_once 'config/db_connect.php';

            $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
            $resetHandler = new PasswordReset($conn);

            if (!$token || !$resetHandler->validateResetToken($token)) {
                echo '<div class="alert alert-danger">Invalid or expired reset link. Please request a new password reset.</div>';
                echo '<p><a href="reset_password.php">Back to Reset Password</a></p>';
                exit;
            }
            ?>
            
            <form id="newPasswordForm" action="process_new_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-requirements">
                        Password must:
                        <ul>
                            <li>Be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long</li>
                            <?php if (PASSWORD_REQUIRE_UPPERCASE): ?>
                                <li>Include at least one uppercase letter</li>
                            <?php endif; ?>
                            <?php if (PASSWORD_REQUIRE_LOWERCASE): ?>
                                <li>Include at least one lowercase letter</li>
                            <?php endif; ?>
                            <?php if (PASSWORD_REQUIRE_NUMBERS): ?>
                                <li>Include at least one number</li>
                            <?php endif; ?>
                            <?php if (PASSWORD_REQUIRE_SPECIAL): ?>
                                <li>Include at least one special character</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Set New Password</button>
            </form>
        </div>
    </div>
    
    <script>
    document.getElementById('newPasswordForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return;
        }
        
        // Password strength validation
        const minLength = <?php echo PASSWORD_MIN_LENGTH; ?>;
        if (password.length < minLength) {
            e.preventDefault();
            alert('Password must be at least ' + minLength + ' characters long!');
            return;
        }
        
        <?php if (PASSWORD_REQUIRE_UPPERCASE): ?>
        if (!/[A-Z]/.test(password)) {
            e.preventDefault();
            alert('Password must contain at least one uppercase letter!');
            return;
        }
        <?php endif; ?>
        
        <?php if (PASSWORD_REQUIRE_LOWERCASE): ?>
        if (!/[a-z]/.test(password)) {
            e.preventDefault();
            alert('Password must contain at least one lowercase letter!');
            return;
        }
        <?php endif; ?>
        
        <?php if (PASSWORD_REQUIRE_NUMBERS): ?>
        if (!/[0-9]/.test(password)) {
            e.preventDefault();
            alert('Password must contain at least one number!');
            return;
        }
        <?php endif; ?>
        
        <?php if (PASSWORD_REQUIRE_SPECIAL): ?>
        if (!/[^A-Za-z0-9]/.test(password)) {
            e.preventDefault();
            alert('Password must contain at least one special character!');
            return;
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>
