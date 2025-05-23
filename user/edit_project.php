<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure user is logged in
requireLogin();

// Get project ID from URL
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Fetch project details
$sql = "SELECT * FROM projects WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $project_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);

// If project not found, not pending, or doesn't belong to user, redirect to dashboard
if (!$project) {
    $_SESSION['error'] = "Project not found or cannot be edited.";
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $institution = mysqli_real_escape_string($conn, $_POST['institution']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);

    // Update project
    $update_sql = "UPDATE projects SET 
                    project_name = ?,
                    description = ?,
                    institution = ?,
                    location = ?,
                    contact = ?,
                    updated_at = CURRENT_TIMESTAMP
                  WHERE id = ? AND user_id = ?";
    
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "sssssii", 
        $project_name, $description, $institution, $location, $contact, $project_id, $user_id);

    if (mysqli_stmt_execute($update_stmt)) {
        // Handle file upload if provided
        if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] == 0) {
            $file_name = $_FILES['project_file']['name'];
            $file_tmp = $_FILES['project_file']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Generate unique filename
            $new_file_name = uniqid() . '_' . $file_name;
            $upload_path = '../uploads/projects/' . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Update or insert file record
                $file_sql = "INSERT INTO project_files (project_id, file_name, file_path) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            file_name = VALUES(file_name), 
                            file_path = VALUES(file_path)";
                $file_stmt = mysqli_prepare($conn, $file_sql);
                $file_path = 'uploads/projects/' . $new_file_name;
                mysqli_stmt_bind_param($file_stmt, "iss", $project_id, $file_name, $file_path);
                mysqli_stmt_execute($file_stmt);
            }
        }

        $_SESSION['message'] = "Project updated successfully.";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Error updating project. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Project Management System</title>
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

    <div class="container">
        <h2>Edit Project</h2>

        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="project_name">Project Name</label>
                    <input type="text" id="project_name" name="project_name" 
                           value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="6" required><?php 
                        echo htmlspecialchars($project['description']); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="institution">Institution</label>
                    <input type="text" id="institution" name="institution" 
                           value="<?php echo htmlspecialchars($project['institution']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo htmlspecialchars($project['location']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Information</label>
                    <input type="text" id="contact" name="contact" 
                           value="<?php echo htmlspecialchars($project['contact']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="project_file">Update Project File (optional)</label>
                    <input type="file" id="project_file" name="project_file" accept=".pdf,.doc,.docx">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Update Project</button>
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
