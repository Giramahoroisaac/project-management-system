<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure user is logged in
requireLogin();

// Get user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Project Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">
            <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li class="nav-item"><a href="submit_project.php" class="nav-link">Submit Project</a></li>
            <li class="nav-item"><a href="../includes/logout.php" class="nav-link">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>My Profile</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form action="../includes/profile_process.php"      method="POST" enctype="multipart/form-data" onsubmit="return validateProfileForm(this);">                <div class="profile-image-container">
                    <div class="profile-image" id="profileImagePreview">
                        <?php if ($user['profile_image']): ?>
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image">
                        <?php else: ?>
                            <div class="profile-placeholder">
                                <i class="placeholder-text">No Image</i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-image-upload">
                        <label for="profile_image" class="custom-file-upload">
                            <span class="upload-icon">📷</span>
                            Choose Profile Photo
                        </label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif" class="hidden-input">
                        <small class="file-requirements">
                            Accepted formats: JPG, PNG, GIF (Max size: 2MB)<br>
                            Recommended size: 300x300 pixels
                        </small>
                        <div id="imageError" class="error-message"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" minlength="6">
                    <small>Leave blank to keep current password</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                </div>

                <button type="submit" class="btn-primary">Update Profile</button>
            </form>
        </div>
    </div>    <script src="../js/validation.js"></script>
    <script>
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const errorElement = document.getElementById('imageError');
            const preview = document.getElementById('profileImagePreview');
            
            // Reset error
            errorElement.style.display = 'none';
            errorElement.textContent = '';
            
            if (file) {
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    errorElement.textContent = 'File size must be less than 2MB';
                    errorElement.style.display = 'block';
                    this.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    errorElement.textContent = 'Please upload a valid image file (JPG, PNG, or GIF)';
                    errorElement.style.display = 'block';
                    this.value = '';
                    return;
                }
                
                // Create preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Profile Image Preview">`;
                };
                reader.readAsDataURL(file);
                
                // Create image object to check dimensions
                const img = new Image();
                img.src = URL.createObjectURL(file);
                img.onload = function() {
                    if (this.width < 200 || this.height < 200) {
                        errorElement.textContent = 'Image dimensions should be at least 200x200 pixels';
                        errorElement.style.display = 'block';
                    }
                    URL.revokeObjectURL(this.src);
                };
            }
        });
    </script>
</body>
</html>
