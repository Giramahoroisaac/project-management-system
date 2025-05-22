<?php
require_once '../config/security_config.php';
require_once '../config/db_connect.php';
require_once 'auth.php';

class PasswordReset {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function createResetToken($email) {
        // Verify user exists
        $stmt = $this->conn->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false; // Don't reveal if email exists or not
        }
        
        $user = $result->fetch_assoc();
        
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+' . PASSWORD_RESET_EXPIRY . ' minutes'));
        
        // Store token
        $stmt = $this->conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $token, $expires);
        
        if ($stmt->execute()) {
            // Send reset email
            $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
            $to = $user['email'];
            $subject = "Password Reset Request";
            $message = "Click the following link to reset your password: {$resetLink}\n\n";
            $message .= "This link will expire in " . PASSWORD_RESET_EXPIRY . " minutes.\n";
            $message .= "If you didn't request this reset, please ignore this email.";
            
            $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            return mail($to, $subject, $message, $headers);
        }
        
        return false;
    }
    
    public function validateResetToken($token) {
        $stmt = $this->conn->prepare("
            SELECT user_id 
            FROM password_reset_tokens 
            WHERE token = ? 
            AND expires_at > NOW() 
            AND used = 0
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc()['user_id'];
        }
        return false;
    }
    
    public function resetPassword($token, $newPassword) {
        $userId = $this->validateResetToken($token);
        if (!$userId) {
            return false;
        }
        
        // Validate password strength
        if (!$this->validatePasswordStrength($newPassword)) {
            return false;
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            // Mark token as used
            $stmt = $this->conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            return true;
        }
        
        return false;
    }
    
    private function validatePasswordStrength($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }
        
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
}
?>
