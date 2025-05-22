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

// Handle project status updates
if (isset($_POST['project_id']) && isset($_POST['action'])) {
    $project_id = mysqli_real_escape_string($conn, $_POST['project_id']);
    $action = $_POST['action'];
    $comment = isset($_POST['comment']) ? mysqli_real_escape_string($conn, $_POST['comment']) : '';
    
    switch($action) {
        case 'approve':
            $status = 'approved';
            $message = "Project approved successfully";
            break;
        case 'reject':
            $status = 'rejected';
            $message = "Project rejected successfully";
            break;
    }

    if (isset($status)) {
        mysqli_begin_transaction($conn);
        try {
            // Update project status
            $sql = "UPDATE projects SET status = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $status, $project_id);
            mysqli_stmt_execute($stmt);

            // Log the action
            $description = "$status project ID: $project_id" . ($comment ? " - Comment: $comment" : "");
            logAction($conn, 'project_' . $action, $description);

            mysqli_commit($conn);
            $_SESSION['message'] = $message;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Error updating project status";
        }
    }
}

// Get projects with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query
$where_clause = "WHERE 1=1";
if ($search) {
    $where_clause .= " AND (p.project_name LIKE '%$search%' OR p.institution LIKE '%$search%')";
}
if ($status_filter) {
    $where_clause .= " AND p.status = '$status_filter'";
}

$sql = "SELECT p.*, u.first_name, u.last_name 
        FROM projects p
        JOIN users u ON p.user_id = u.id 
        $where_clause 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as count FROM projects p JOIN users u ON p.user_id = u.id $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_projects = mysqli_fetch_assoc($count_result)['count'];
$total_pages = ceil($total_projects / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - Project Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">
            <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li class="nav-item"><a href="../includes/logout.php" class="nav-link">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Manage Projects</h2>

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

        <div class="filters">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search projects..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <button type="submit" class="btn-primary">Filter</button>
            </form>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Submitted By</th>
                        <th>Institution</th>
                        <th>Status</th>
                        <th>Submission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($project = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['institution']); ?></td>
                        <td><?php echo ucfirst($project['status']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                        <td>
                            <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn-small">View</a>
                            <?php if ($project['status'] == 'pending'): ?>
                                <button onclick="showReviewModal(<?php echo $project['id']; ?>)" class="btn-small">Review</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Review Project</h3>
            <form id="reviewForm" method="POST">
                <input type="hidden" name="project_id" id="modal_project_id">
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
        
        function showReviewModal(projectId) {
            document.getElementById('modal_project_id').value = projectId;
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
