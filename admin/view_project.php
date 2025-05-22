<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure admin has project review permission
requireLogin();
if (!hasPermission('review_projects')) {
    $_SESSION['error'] = "Access denied";
    header("Location: dashboard.php");
    exit();
}

// Get project details
if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit();
}

$project_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT p.*, u.first_name, u.last_name, u.email
        FROM projects p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$project = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Project not found";
    header("Location: projects.php");
    exit();
}

// Get project files
$sql = "SELECT * FROM project_files WHERE project_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$files_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Project - Project Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">
            <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li class="nav-item"><a href="projects.php" class="nav-link">All Projects</a></li>
            <li class="nav-item"><a href="../includes/logout.php" class="nav-link">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="project-header">
            <h2>Project Details</h2>
            <?php if ($project['status'] == 'pending'): ?>
                <button onclick="showReviewModal()" class="btn-primary">Review Project</button>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="project-details card">
            <div class="detail-group">
                <h3>Project Information</h3>
                <table class="detail-table">
                    <tr>
                        <th>Project Name:</th>
                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td><span class="status-badge <?php echo $project['status']; ?>"><?php echo ucfirst($project['status']); ?></span></td>
                    </tr>
                    <tr>
                        <th>Submission Date:</th>
                        <td><?php echo date('M d, Y H:i', strtotime($project['created_at'])); ?></td>
                    </tr>
                </table>
            </div>

            <div class="detail-group">
                <h3>Submitter Information</h3>
                <table class="detail-table">
                    <tr>
                        <th>Name:</th>
                        <td><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($project['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Contact:</th>
                        <td><?php echo htmlspecialchars($project['contact']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="detail-group">
                <h3>Institution Details</h3>
                <table class="detail-table">
                    <tr>
                        <th>Institution:</th>
                        <td><?php echo htmlspecialchars($project['institution']); ?></td>
                    </tr>
                    <tr>
                        <th>Location:</th>
                        <td><?php echo htmlspecialchars($project['location']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="detail-group">
                <h3>Project Description</h3>
                <div class="description-box">
                    <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                </div>
            </div>

            <div class="detail-group">
                <h3>Attached Files</h3>
                <div class="files-list">
                    <?php while ($file = mysqli_fetch_assoc($files_result)): ?>
                        <div class="file-item">
                            <span class="file-name"><?php echo htmlspecialchars($file['file_name']); ?></span>
                            <a href="../uploads/projects/<?php echo htmlspecialchars($file['file_path']); ?>" 
                               class="btn-small" target="_blank">View</a>
                            <a href="../uploads/projects/<?php echo htmlspecialchars($file['file_path']); ?>" 
                               class="btn-small" download>Download</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Review Project</h3>
            <form action="projects.php" method="POST">
                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                <div class="form-group">
                    <label for="comment">Comment (optional):</label>
                    <textarea name="comment" id="comment" rows="4"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="action" value="approve" class="btn-primary">Approve</button>
                    <button type="submit" name="action" value="reject" class="btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/admin.js"></script>
    <script>
        // Modal functionality
        const modal = document.getElementById('reviewModal');
        const span = document.getElementsByClassName('close')[0];
        
        function showReviewModal() {
            modal.style.display = 'block';
        }
        
        span.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
