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
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">
            <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li class="nav-item"><a href="users.php" class="nav-link">User Management</a></li>
            <li class="nav-item"><a href="projects.php" class="nav-link">Project Review</a></li>
            <li class="nav-item"><a href="../includes/logout.php" class="nav-link">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2>Pending Approvals</h2>
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
                <h3>Pending User Registrations</h3>
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
                                        <button onclick="approveUser(<?php echo $user['id']; ?>)" class="btn-small btn-success">Approve</button>
                                        <button onclick="rejectUser(<?php echo $user['id']; ?>)" class="btn-small btn-danger">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No pending user registrations</p>
                <?php endif; ?>
            </div>

            <!-- Pending Projects -->
            <div class="card">
                <h3>Pending Project Reviews</h3>
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
                                        <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn-small">Review</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No pending projects</p>
                <?php endif; ?>
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
