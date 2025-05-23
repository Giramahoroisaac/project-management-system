<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure user is logged in
requireLogin();

// Get project ID from URL
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Fetch project details with JOIN to get attached files
$sql = "SELECT p.*, pf.file_name, pf.file_path 
        FROM projects p 
        LEFT JOIN project_files pf ON p.id = pf.project_id 
        WHERE p.id = ? AND p.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $project_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);

// If project not found or doesn't belong to user, redirect to dashboard
if (!$project) {
    $_SESSION['error'] = "Project not found or access denied.";
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Project - Project Management System</title>
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

    </nav>

    <div class="main-content">
        <div class="dashboard-header">
            <h2><i class="fas fa-project-diagram"></i> Project Details</h2>
            <div class="header-actions">
                <?php if ($project['status'] === 'pending'): ?>
                    <a href="edit_project.php?id=<?php echo $project_id; ?>" class="btn-small btn-primary">
                        <i class="fas fa-edit"></i> Edit Project
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="project-details-container">
            <!-- Project Status Card -->
            <div class="status-card">
                <div class="status-header">
                    <h3>Project Status</h3>
                    <span class="status-badge <?php echo $project['status']; ?>">
                        <?php echo ucfirst($project['status']); ?>
                    </span>
                </div>
                <div class="status-info">
                    <p><strong>Submitted:</strong> <?php echo date('F d, Y', strtotime($project['created_at'])); ?></p>
                    <?php if ($project['updated_at']): ?>
                        <p><strong>Last Updated:</strong> <?php echo date('F d, Y', strtotime($project['updated_at'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Project Information -->
            <div class="detail-section">
                <h3 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Project Name</label>
                        <p><?php echo htmlspecialchars($project['project_name']); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Institution</label>
                        <p><?php echo htmlspecialchars($project['institution']); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Location</label>
                        <p><?php echo htmlspecialchars($project['location']); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Contact Information</label>
                        <p><?php echo htmlspecialchars($project['contact']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Project Description -->
            <div class="detail-section">
                <h3 class="section-title"><i class="fas fa-align-left"></i> Project Description</h3>
                <div class="description-box">
                    <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                </div>
            </div>

            <!-- Project Files -->
            <div class="detail-section">
                <h3 class="section-title"><i class="fas fa-paperclip"></i> Attached Files</h3>
                <div class="files-grid">
                    <?php
                    $sql = "SELECT * FROM project_files WHERE project_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $project_id);
                    mysqli_stmt_execute($stmt);
                    $files_result = mysqli_stmt_get_result($stmt);
                    
                    while ($file = mysqli_fetch_assoc($files_result)):
                        $file_extension = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                        $icon_class = '';
                        switch(strtolower($file_extension)) {
                            case 'pdf': $icon_class = 'fa-file-pdf'; break;
                            case 'doc':
                            case 'docx': $icon_class = 'fa-file-word'; break;
                            case 'txt': $icon_class = 'fa-file-alt'; break;
                            case 'zip': $icon_class = 'fa-file-archive'; break;
                            default: $icon_class = 'fa-file';
                        }
                    ?>
                        <div class="file-item">
                            <i class="fas <?php echo $icon_class; ?>"></i>
                            <span class="file-name"><?php echo htmlspecialchars($file['file_name']); ?></span>
                            <a href="<?php echo htmlspecialchars($file['file_path']); ?>" class="btn-small btn-primary" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <?php if ($project['admin_comment']): ?>
            <!-- Admin Comments -->
            <div class="detail-section">
                <h3 class="section-title"><i class="fas fa-comments"></i> Admin Comments</h3>
                <div class="admin-comment">
                    <?php echo nl2br(htmlspecialchars($project['admin_comment'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
