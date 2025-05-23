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
    <title>Projects - Project Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">
            <li class="nav-item">
                <i class="fas fa-user-shield"></i>
                <span class="user-welcome">Welcome, <?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User'; ?></span>
                <span class="role-badge <?php echo $_SESSION['role']; ?>"><?php echo ucwords(str_replace('_', ' ', $_SESSION['role'])); ?></span>
            </li>
            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? ' active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? ' active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="roles.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? ' active' : ''; ?>">
                        <i class="fas fa-user-tag"></i>
                        <span>Manage Roles</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="projects.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? ' active' : ''; ?>">
                        <i class="fas fa-project-diagram"></i>
                        <span>All Projects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? ' active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="audit_logs.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'audit_logs.php' ? ' active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a href="users.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? ' active' : ''; ?>">
                        <i class="fas fa-users-cog"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="projects.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? ' active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Project Review</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pending_approvals.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'pending_approvals.php' ? ' active' : ''; ?>">
                        <i class="fas fa-clock"></i>
                        <span>Pending Approvals</span>
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="../includes/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>   
    </nav>

    <div class="main-content">
        <div class="dashboard-header">
            <h2><i class="fas fa-project-diagram"></i> Project Management</h2>
            <p class="text-muted">Review and manage all submitted projects</p>
        </div>

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

        <div class="project-filters">
            <div class="form-group">
                <select id="statusFilter" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Submitted By</th>
                        <th>Status</th>
                        <th>Submission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get all projects
                    $sql = "SELECT p.*, u.first_name, u.last_name 
                            FROM projects p 
                            JOIN users u ON p.user_id = u.id 
                            ORDER BY p.created_at DESC";
                    $projects = mysqli_query($conn, $sql);

                    while ($project = mysqli_fetch_assoc($projects)):
                        $statusClass = '';
                        switch($project['status']) {
                            case 'pending': $statusClass = 'badge-warning'; break;
                            case 'approved': $statusClass = 'badge-success'; break;
                            case 'rejected': $statusClass = 'badge-danger'; break;
                        }
                    ?>
                    <tr data-status="<?php echo htmlspecialchars($project['status']); ?>">
                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></td>
                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($project['status']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn-small">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($project['status'] === 'pending'): ?>
                                <button onclick="reviewProject(<?php echo $project['id']; ?>)" class="btn-small btn-primary">
                                    <i class="fas fa-check"></i> Review
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Filter projects by status
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (!status || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Project review function
        function reviewProject(projectId) {
            // Add your review logic here
            window.location.href = `view_project.php?id=${projectId}&action=review`;
        }
    </script>
</body>
</html>
