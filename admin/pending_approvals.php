<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure admin access
requireAdmin();

// Only allow sub_admin access to this page
if ($_SESSION['role'] !== 'sub_admin') {
    header('Location: dashboard.php');
    exit();
}

// Get pending users
$sql = "SELECT * FROM users WHERE role = 'user' AND status = 'pending' ORDER BY created_at DESC";
$pending_users = mysqli_query($conn, $sql);

// Get pending projects
$sql = "SELECT p.*, u.first_name, u.last_name 
        FROM projects p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'pending' 
        ORDER BY p.created_at DESC";
$pending_projects = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - Project Management System</title>
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
            <h2><i class="fas fa-clock"></i> Pending Approvals</h2>
            <p class="text-muted">Review and manage pending user registrations and project submissions</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Pending User Registrations -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Pending User Registrations</h3>
                </div>
                <div class="table-responsive">
                    <?php if (mysqli_num_rows($pending_users) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = mysqli_fetch_assoc($pending_users)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="approveUser(<?php echo $user['id']; ?>)" class="btn-small btn-success">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button onclick="rejectUser(<?php echo $user['id']; ?>)" class="btn-small btn-danger">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-info-circle"></i>
                            <p>No pending user registrations</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Projects -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-project-diagram"></i> Pending Project Reviews</h3>
                </div>
                <div class="table-responsive">
                    <?php if (mysqli_num_rows($pending_projects) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Submitted By</th>
                                    <th>Submission Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($project = mysqli_fetch_assoc($pending_projects)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                    <td><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn-small btn-primary">
                                                <i class="fas fa-eye"></i> Review
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-info-circle"></i>
                            <p>No pending projects</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function approveUser(userId) {
        if (confirm('Are you sure you want to approve this user?')) {
            window.location.href = `users.php?action=approve&id=${userId}`;
        }
    }

    function rejectUser(userId) {
        if (confirm('Are you sure you want to reject this user?')) {
            window.location.href = `users.php?action=reject&id=${userId}`;
        }
    }
    </script>
</body>
</html>
