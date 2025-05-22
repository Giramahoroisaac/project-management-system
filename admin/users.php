<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure admin has user management permission
requireLogin();
if (!hasPermission('manage_users')) {
    $_SESSION['error'] = "Access denied";
    header("Location: dashboard.php");
    exit();
}

// Handle status updates if submitted
if (isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $action = $_POST['action'];
    
    switch($action) {
        case 'approve':
            $sql = "UPDATE users SET status = 'active' WHERE id = ?";
            $message = "User approved successfully";
            break;
        case 'disable':
            $sql = "UPDATE users SET status = 'disabled' WHERE id = ?";
            $message = "User disabled successfully";
            break;
        case 'enable':
            $sql = "UPDATE users SET status = 'active' WHERE id = ?";
            $message = "User enabled successfully";
            break;
    }

    if (isset($sql)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            logAction($conn, 'user_' . $action, "Admin {$_SESSION['name']} {$action}d user ID: {$user_id}");
            $_SESSION['message'] = $message;
        } else {
            $_SESSION['error'] = "Error updating user status";
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query
$where_clause = "WHERE role = 'user'";
if ($search) {
    $where_clause .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($status_filter) {
    $where_clause .= " AND status = '$status_filter'";
}

$sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as count FROM users $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_users = mysqli_fetch_assoc($count_result)['count'];
$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Project Management System</title>
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
        <h2>Manage Users</h2>

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
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="disabled" <?php echo $status_filter == 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                </select>
                <button type="submit" class="btn-primary">Filter</button>
            </form>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst($user['status']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <?php if ($user['status'] == 'pending'): ?>
                                    <button type="submit" name="action" value="approve" class="btn-small">Approve</button>
                                <?php elseif ($user['status'] == 'active'): ?>
                                    <button type="submit" name="action" value="disable" class="btn-small btn-danger">Disable</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="enable" class="btn-small">Enable</button>
                                <?php endif; ?>
                            </form>
                            <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn-small">View</a>
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

    <script src="../js/admin.js"></script>
</body>
</html>
