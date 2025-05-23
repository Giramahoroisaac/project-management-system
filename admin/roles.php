<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure super admin access
requireSuperAdmin();

// Handle role updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $new_role = mysqli_real_escape_string($conn, $_POST['role']);
      // Validate role
    $valid_roles = ['super_admin', 'sub_admin', 'user'];
    if (in_array($new_role, $valid_roles)) {
        mysqli_begin_transaction($conn);
        try {
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Log the role change
                $description = "Changed user ID: $user_id role to $new_role";
                logAction($conn, 'role_change', $description);
                
                mysqli_commit($conn);
                $_SESSION['message'] = "Role updated successfully";
            } else {
                throw new Exception("Error updating role");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';

// Build query
$where_clause = "WHERE 1=1";
if ($search) {
    $where_clause .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($role_filter) {
    $where_clause .= " AND role = '$role_filter'";
}

$sql = "SELECT * FROM users $where_clause ORDER BY role, created_at DESC LIMIT ? OFFSET ?";
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
    <title>Manage Roles - Project Management System</title>
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
            <?php endif; ?>
            <li class="nav-item">
                <a href="../includes/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>   
    </nav>

    <div class="container">
        <h2>Role Management</h2>

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
        <?php endif; ?>            <div class="role-info card">
            <h3>Role Descriptions</h3>
            <ul class="role-list">
                <li><strong>Super Admin:</strong> Full system access, can manage all users and their roles</li>
                <li><strong>Sub Admin:</strong> Combined administrative privileges:
                    <ul>
                        <li>Manage user accounts and reset passwords</li>
                        <li>Approve new user registrations</li>
                        <li>Review and approve/reject projects</li>
                    </ul>
                </li>
                <li><strong>User:</strong> Standard user access for submitting projects</li>
            </ul>
        </div>

        <div class="filters">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">                <select name="role">
                    <option value="">All Roles</option>
                    <option value="super_admin" <?php echo $role_filter == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    <option value="sub_admin" <?php echo $role_filter == 'sub_admin' ? 'selected' : ''; ?>>Sub Admin</option>
                    <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>User</option>
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
                        <th>Current Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></td>
                        <td><?php echo ucfirst($user['status']); ?></td>
                        <td>
                            <button onclick="showRoleModal(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')" 
                                    class="btn-small">Change Role</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Role Change Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Change User Role</h3>
            <form method="POST" id="roleForm">
                <input type="hidden" name="user_id" id="modal_user_id">
                <div class="form-group">
                    <label for="role">Select New Role:</label>                    <select name="role" id="role" required>
                        <option value="super_admin">Super Admin</option>
                        <option value="sub_admin">Sub Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('roleModal');
        const span = document.getElementsByClassName('close')[0];
        
        function showRoleModal(userId, currentRole) {
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('role').value = currentRole;
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

        // Confirm role change
        document.getElementById('roleForm').addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to change this user\'s role?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
