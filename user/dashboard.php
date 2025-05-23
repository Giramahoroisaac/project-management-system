<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure user is logged in
requireLogin();

// Get user's projects
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Project Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">            <li class="nav-item user-profile">
                <div class="user-avatar">
                    <?php
                    $profile_image = isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : '';
                    if ($profile_image && file_exists("../uploads/profiles/" . $profile_image)) {
                        echo '<img src="../uploads/profiles/' . $profile_image . '" alt="Profile">';
                    } else {
                        echo '<i class="fas fa-user-circle"></i>';
                    }
                    ?>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Welcome,</span>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                </div>
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
            <h2><i class="fas fa-project-diagram"></i> My Projects</h2>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Institution</th>
                        <th>Status</th>
                        <th>Submitted Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($project = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['institution']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $project['status']; ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                        <td>
                            <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn-small">View</a>
                            <?php if ($project['status'] == 'pending'): ?>
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn-small">Edit</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="5" class="text-center">No projects submitted yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../js/dashboard.js"></script>
</body>
</html>
