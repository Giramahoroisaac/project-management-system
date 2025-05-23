<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure user is logged in
requireLogin();

// Fetch user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Error getting result: " . $stmt->error);
}

$user = $result->fetch_assoc();
$userName = htmlspecialchars($user['full_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Project - Project Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

    </nav>    <div class="main-content">
        <div class="dashboard-header">
            <h2><i class="fas fa-plus-circle"></i> Submit New Project</h2>
            <p class="text-muted">Please fill in all required information for your project submission</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="project-form-card">
            <form action="../includes/project_process.php" method="POST" enctype="multipart/form-data" onsubmit="return validateProjectForm(this);">             <div class="form-section">
                    <h3 class="form-section-title">Basic Information</h3>
                    <div class="form-group">
                        <label for="name_of_user">Name of User</label>
                        <input type="text" id="name_of_user" name="name_of_user" value="<?php echo $userName; ?>" readonly class="readonly-input">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    </div>   

                    <div class="form-group">
                        <label for="project_name">Project Name *</label>
                        <input type="text" id="project_name" name="project_name" required 
                               placeholder="Enter your project name">
                    </div>  
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Project Details</h3>
                    <div class="form-group">
                        <label for="description">Project Description *</label>
                        <div class="textarea-container">
                            <textarea id="description" name="description" rows="6" minlength="100" maxlength="2000" 
                                placeholder="Provide a detailed description of your project including:
• Project objectives and goals
• Methodology and approach
• Expected outcomes and impact
• Timeline and milestones
• Required resources" required></textarea>
                            <div class="textarea-footer">
                                <small class="description-guide">Minimum 100 characters, maximum 2000 characters</small>
                                <span class="char-count" id="descriptionCount">0 / 2000</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Contact Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="institution">Institution *</label>
                            <input type="text" id="institution" name="institution" required 
                                   placeholder="Enter your institution name">
                        </div>

                        <div class="form-group">
                            <label for="location">Location *</label>
                            <input type="text" id="location" name="location" required 
                                   placeholder="Enter project location">
                        </div>

                        <div class="form-group">
                            <label for="contact">Contact Information *</label>
                            <input type="text" id="contact" name="contact" required 
                                   placeholder="Enter your contact details">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Project Files</h3>
                    <div class="file-upload-container" id="dropZone">
                        <div class="file-upload-icon">📁</div>
                        <input type="file" id="project_files" name="project_files[]" multiple 
                               accept=".pdf,.doc,.docx,.txt,.zip" required class="hidden-input">
                        <label for="project_files">
                            Drag & drop files here or click to browse
                        </label>
                        <div class="file-requirements">
                            <strong>Requirements:</strong><br>
                            • Maximum file size: 10MB per file<br>
                            • Allowed formats: PDF, DOC, DOCX, TXT, ZIP
                        </div>
                    </div>
                    <div id="fileList" class="file-list"></div>
                </div>

                <button type="submit" class="btn-submit-project">Submit Project</button>
            </form>
        </div>
    </div>

    <script src="../js/validation.js"></script>    <script>
        // Character count for description
        document.getElementById('description').addEventListener('input', function() {
            const maxLength = 2000;
            const currentLength = this.value.length;
            const countDisplay = document.getElementById('descriptionCount');
            
            countDisplay.textContent = `${currentLength} / ${maxLength}`;
            
            // Visual feedback
            if (currentLength < 100) {
                countDisplay.style.color = '#dc3545';
            } else if (currentLength > 1800) {
                countDisplay.style.color = '#ffc107';
            } else {
                countDisplay.style.color = '#666';
            }
        });

        function validateProjectForm(form) {
            // Validate description length
            const description = form.querySelector('#description').value;
            if (description.length < 100) {
                alert('Project description must be at least 100 characters long.');
                return false;
            }
            
            const files = form.querySelector('#project_files').files;
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'application/zip'
            ];

            for (let i = 0; i < files.length; i++) {
                if (files[i].size > maxSize) {
                    alert('File ' + files[i].name + ' is too large. Maximum size is 10MB.');
                    return false;
                }
                if (!allowedTypes.includes(files[i].type)) {
                    alert('File ' + files[i].name + ' is not an allowed file type.');
                    return false;
                }
            }
            return true;
        }
    </script>
</body>
</html>
