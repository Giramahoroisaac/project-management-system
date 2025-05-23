<?php
// Set session cookie parameters for better security and path handling
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_path', '/doc_project/');

session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Debug information
error_log("Session data in dashboard: " . print_r($_SESSION, true));

// Check session validity
if (!isset($_SESSION['auth_time']) || (time() - $_SESSION['auth_time'] > 7200)) {
    session_destroy();
    header("Location: /doc_project/login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Ensure admin access
requireAdmin();

// Get counts for dashboard
$counts = array();

// Get total users count
$sql = "SELECT COUNT(*) as count, status FROM users GROUP BY status";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $counts['users_' . $row['status']] = $row['count'];
}

// Get total projects count
$sql = "SELECT COUNT(*) as count, status FROM projects GROUP BY status";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $counts['projects_' . $row['status']] = $row['count'];
}

// Get recent projects
$sql = "SELECT p.*, u.first_name, u.last_name FROM projects p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC LIMIT 5";
$recent_projects = mysqli_query($conn, $sql);

// Get recent user registrations
$sql = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5";
$recent_users = mysqli_query($conn, $sql);

// Get pending user registrations
$sql = "SELECT * FROM users WHERE role = 'user' AND status = 'pending' ORDER BY created_at DESC LIMIT 5";
$pending_users = mysqli_query($conn, $sql);

// Get projects pending review
$sql = "SELECT p.*, u.first_name, u.last_name FROM projects p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC LIMIT 5";
$pending_projects = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Project Management System</title>
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
    </nav>    <div class="main-content">
        <div class="dashboard-header">
            <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard Overview</h2>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Users</h3>
                <p>Active: <?php echo $counts['users_active'] ?? 0; ?></p>
                <p>Pending: <?php echo $counts['users_pending'] ?? 0; ?></p>
                <p>Disabled: <?php echo $counts['users_disabled'] ?? 0; ?></p>
            </div>

            <div class="stat-card">
                <h3>Projects</h3>
                <p>Pending: <?php echo $counts['projects_pending'] ?? 0; ?></p>
                <p>Approved: <?php echo $counts['projects_approved'] ?? 0; ?></p>
                <p>Rejected: <?php echo $counts['projects_rejected'] ?? 0; ?></p>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Recent Projects</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($project = mysqli_fetch_assoc($recent_projects)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                            <td><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></td>
                            <td><?php echo ucfirst($project['status']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                            <td>
                                <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn-small">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="projects.php" class="btn-link">View All Projects</a>
            </div>            <?php if ($_SESSION['role'] === 'super_admin'): ?>
            <div class="card">
                <h3>Recent User Registrations</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($recent_users)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst($user['status']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn-small">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="users.php" class="btn-link">View All Users</a>
            </div>
            <?php else: ?>
            <div class="card">
                <h3>Pending User Registrations</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($pending_users) > 0): ?>
                            <?php while ($user = mysqli_fetch_assoc($pending_users)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="users.php?action=approve&id=<?php echo $user['id']; ?>" class="btn-small btn-success">Approve</a>
                                        <a href="users.php?action=reject&id=<?php echo $user['id']; ?>" class="btn-small btn-danger">Reject</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="no-data">No pending user registrations</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <a href="pending_approvals.php" class="btn-link">View All Pending Approvals</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/admin.js"></script>
</body>
</html>
