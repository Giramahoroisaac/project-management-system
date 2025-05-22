<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure admin access
requireAdmin();

// Initialize filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$action_filter = isset($_GET['action']) ? mysqli_real_escape_string($conn, $_GET['action']) : '';
$user_filter = isset($_GET['user']) ? mysqli_real_escape_string($conn, $_GET['user']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query
$where_clause = "WHERE 1=1";
if ($action_filter) {
    $where_clause .= " AND al.action = '$action_filter'";
}
if ($user_filter) {
    $where_clause .= " AND (u.first_name LIKE '%$user_filter%' OR u.last_name LIKE '%$user_filter%' OR u.email LIKE '%$user_filter%')";
}
if ($start_date && $end_date) {
    $where_clause .= " AND DATE(al.created_at) BETWEEN '$start_date' AND '$end_date'";
}

// Get unique actions for filter dropdown
$actions_sql = "SELECT DISTINCT action FROM audit_logs ORDER BY action";
$actions_result = mysqli_query($conn, $actions_sql);

// Get audit logs with user details
$sql = "SELECT al.*, u.first_name, u.last_name, u.email, u.role 
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id 
        $where_clause 
        ORDER BY al.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as count FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_logs = mysqli_fetch_assoc($count_result)['count'];
$total_pages = ceil($total_logs / $limit);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Timestamp', 'User', 'Role', 'Action', 'Description']);
    
    // Get all logs without limit
    $export_sql = "SELECT al.*, u.first_name, u.last_name, u.email, u.role 
                   FROM audit_logs al
                   LEFT JOIN users u ON al.user_id = u.id 
                   $where_clause 
                   ORDER BY al.created_at DESC";
    $export_result = mysqli_query($conn, $export_sql);
    
    while ($row = mysqli_fetch_assoc($export_result)) {
        fputcsv($output, [
            $row['created_at'],
            ($row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'System'),
            $row['role'] ?? 'N/A',
            $row['action'],
            $row['description']
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Project Management System</title>
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
        <h2>Audit Logs</h2>

        <div class="audit-filters card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Action:</label>
                    <select name="action">
                        <option value="">All Actions</option>
                        <?php while ($action = mysqli_fetch_assoc($actions_result)): ?>
                            <option value="<?php echo htmlspecialchars($action['action']); ?>"
                                    <?php echo $action_filter == $action['action'] ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $action['action'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>User Search:</label>
                    <input type="text" name="user" placeholder="Search by name or email" 
                           value="<?php echo htmlspecialchars($user_filter); ?>">
                </div>
                <div class="form-group">
                    <label>Date Range:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                    <span>to</span>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                       class="btn-secondary">Export to CSV</a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table audit-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <?php if ($log['user_id']): ?>
                                    <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                    <small>(<?php echo htmlspecialchars($log['email']); ?>)</small>
                                <?php else: ?>
                                    System
                                <?php endif; ?>
                            </td>
                            <td><?php echo $log['role'] ? ucfirst(str_replace('_', ' ', $log['role'])) : 'N/A'; ?></td>
                            <td>
                                <span class="action-badge <?php echo str_replace('_', '-', $log['action']); ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $log['action'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center">No audit logs found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add datepicker validation
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.addEventListener('change', function() {
                const startDate = document.querySelector('input[name="start_date"]').value;
                const endDate = document.querySelector('input[name="end_date"]').value;
                
                if (startDate && endDate && startDate > endDate) {
                    alert('Start date cannot be later than end date');
                    this.value = '';
                }
            });
        });
    </script>
</body>
</html>
