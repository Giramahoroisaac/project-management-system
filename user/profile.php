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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Profile Page Specific Styles */
.container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-top: 1.5rem;
}

.profile-image-section {
    margin-bottom: 2rem;
    text-align: center;
}

.profile-image-container {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
}

.profile-image-wrapper {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    position: relative;
    margin: 0 auto;
    border: 3px solid #e0e0e0;
    transition: all 0.3s ease;
}

.profile-image-wrapper:hover {
    border-color: #4a90e2;
}

.profile-image-current {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    color: #ccc;
}

.profile-image-placeholder i {
    font-size: 5rem;
}

.profile-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.profile-image-wrapper:hover .profile-image-overlay {
    opacity: 1;
}

.profile-image-overlay i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.profile-image-overlay span {
    font-size: 0.9rem;
}

.profile-image-actions {
    position: absolute;
    top: -10px;
    right: -10px;
}

.btn-icon {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #666;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    background: #f5f5f5;
    color: #333;
}

.btn-remove-image {
    color: #e74c3c;
    border-color: #e74c3c;
}

.btn-remove-image:hover {
    background: #fdeaea;
}

.profile-image-meta {
    max-width: 300px;
    margin: 0 auto;
}

.file-requirements {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 0.75rem;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 1rem;
}

.file-requirements p {
    margin: 0.25rem 0;
    display: flex;
    align-items: center;
}

.file-requirements i {
    margin-right: 0.5rem;
    width: 16px;
    text-align: center;
}

.hidden-input {
    display: none;
}

/* Form Styles */
.form-row {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-row .form-group {
    flex: 1;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus {
    border-color: #4a90e2;
    outline: none;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
}

.password-input-wrapper {
    position: relative;
}

.btn-password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 0.25rem;
}

.btn-password-toggle:hover {
    color: #333;
}

.required {
    color: #e74c3c;
}

.hint {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.85rem;
    color: #666;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-primary {
    background: #4a90e2;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    background: #3a7bc8;
}

.btn-primary i {
    font-size: 0.9rem;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-secondary:hover {
    background: #e9e9e9;
}

/* Error Messages */
.error-message {
    color: #e74c3c;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    display: none;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .profile-image-wrapper {
        width: 120px;
        height: 120px;
    }
}
    </style>
</head>
<body>
    <nav class="nav">
    <ul class="nav-list">
            <li class="nav-item">
                <i class="fas fa-user-circle"></i>
                Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
            </li>
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? ' active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="submit_project.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'submit_project.php' ? ' active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> Submit New Project
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? ' active' : ''; ?>">
                    <i class="fas fa-user"></i> My Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="../includes/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
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
            <form action="../includes/profile_process.php" method="POST" enctype="multipart/form-data" onsubmit="return validateProfileForm(this);">
                <div class="profile-image-section">
                    <div class="profile-image-container">
                        <div class="profile-image-wrapper" id="profileImagePreview">
                            <?php if ($user['profile_image']): ?>
                                <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                     alt="Profile Image" class="profile-image-current">
                            <?php else: ?>
                                <div class="profile-image-placeholder">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                            <?php endif; ?>
                            <div class="profile-image-overlay">
                                <i class="fas fa-camera"></i>
                                <span>Change Photo</span>
                            </div>
                        </div>
                        
                        <input type="file" id="profile_image" name="profile_image" 
                               accept="image/jpeg,image/png,image/webp" class="hidden-input">
                        
                        <div class="profile-image-actions">
                            <?php if ($user['profile_image']): ?>
                                <button type="button" class="btn-icon btn-remove-image" id="removeImageBtn" title="Remove photo">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="profile-image-meta">
                        <div class="file-requirements">
                            <p><i class="fas fa-info-circle"></i> Supported formats: JPEG, PNG, WEBP</p>
                            <p><i class="fas fa-expand-alt"></i> Recommended size: 500×500 pixels</p>
                            <p><i class="fas fa-weight-hanging"></i> Max file size: 2MB</p>
                        </div>
                        <div id="imageError" class="error-message"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                    <small class="hint">Format: +1234567890</small>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="new_password" name="new_password" minlength="6">
                        <button type="button" class="btn-password-toggle" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="hint">Minimum 6 characters. Leave blank to keep current password.</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                        <button type="button" class="btn-password-toggle" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/validation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileImageInput = document.getElementById('profile_image');
            const profileImagePreview = document.getElementById('profileImagePreview');
            const removeImageBtn = document.getElementById('removeImageBtn');
            const errorElement = document.getElementById('imageError');
            
            // Handle image selection
            profileImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                // Reset error
                errorElement.style.display = 'none';
                errorElement.textContent = '';
                
                if (file) {
                    // Validate file size (2MB max)
                    if (file.size > 2 * 1024 * 1024) {
                        showImageError('File size must be less than 2MB');
                        return;
                    }
                    
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        showImageError('Please upload a valid image file (JPEG, PNG, or WEBP)');
                        return;
                    }
                    
                    // Create preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileImagePreview.innerHTML = `
                            <img src="${e.target.result}" alt="Profile Image Preview" class="profile-image-current">
                            <div class="profile-image-overlay">
                                <i class="fas fa-camera"></i>
                                <span>Change Photo</span>
                            </div>
                        `;
                        
                        // Show remove button if not already shown
                        if (!removeImageBtn) {
                            const actionsDiv = document.querySelector('.profile-image-actions');
                            actionsDiv.innerHTML = `
                                <button type="button" class="btn-icon btn-remove-image" id="removeImageBtn" title="Remove photo">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            `;
                            document.getElementById('removeImageBtn').addEventListener('click', removeImage);
                        }
                    };
                    reader.readAsDataURL(file);
                    
                    // Check image dimensions
                    const img = new Image();
                    img.src = URL.createObjectURL(file);
                    img.onload = function() {
                        if (this.width < 200 || this.height < 200) {
                            showImageError('For best quality, use an image at least 200x200 pixels');
                        }
                        URL.revokeObjectURL(this.src);
                    };
                }
            });
            
            // Click on preview to trigger file input
            profileImagePreview.addEventListener('click', function() {
                profileImageInput.click();
            });
            
            // Remove image functionality
            if (removeImageBtn) {
                removeImageBtn.addEventListener('click', removeImage);
            }
            
            // Password toggle functionality
            document.querySelectorAll('.btn-password-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            });
            
            function showImageError(message) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                profileImageInput.value = '';
            }
            
            function removeImage() {
                if (confirm('Are you sure you want to remove your profile photo?')) {
                    // Add a hidden field to indicate removal
                    const form = document.querySelector('form');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'remove_profile_image';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    
                    // Update the preview
                    profileImagePreview.innerHTML = `
                        <div class="profile-image-placeholder">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="profile-image-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Add Photo</span>
                        </div>
                    `;
                    
                    // Remove the remove button
                    if (removeImageBtn) {
                        removeImageBtn.remove();
                    }
                }
            }
        });
    </script>
</body>
</html>