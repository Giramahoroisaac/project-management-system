<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure admin access
requireAdmin();

// Initialize date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'projects';

// Function to generate project statistics
function getProjectStats($conn, $start_date, $end_date) {
    $sql = "SELECT 
                COUNT(*) as total_projects,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_projects,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_projects,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_projects
            FROM projects 
            WHERE DATE(created_at) BETWEEN ? AND ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Function to get project details
function getProjectDetails($conn, $start_date, $end_date) {
    $sql = "SELECT p.*, u.first_name, u.last_name, u.email 
            FROM projects p
            JOIN users u ON p.user_id = u.id
            WHERE DATE(p.created_at) BETWEEN ? AND ?
            ORDER BY p.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// Function to generate user statistics
function getUserStats($conn, $start_date, $end_date) {
    $sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_users,
                SUM(CASE WHEN status = 'disabled' THEN 1 ELSE 0 END) as disabled_users
            FROM users 
            WHERE DATE(created_at) BETWEEN ? AND ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Handle report download
if (isset($_GET['download']) && $_GET['download'] == 'true') {
    $filename = 'report_' . $report_type . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if ($report_type == 'projects') {
        // Write projects report headers
        fputcsv($output, ['Project Name', 'Submitter', 'Email', 'Institution', 'Location', 'Status', 'Submission Date']);
        
        $projects = getProjectDetails($conn, $start_date, $end_date);
        while ($row = mysqli_fetch_assoc($projects)) {
            fputcsv($output, [
                $row['project_name'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['email'],
                $row['institution'],
                $row['location'],
                $row['status'],
                $row['created_at']
            ]);
        }
    } else {
        // Write user statistics report headers
        fputcsv($output, ['Metric', 'Count']);
        
        $stats = getUserStats($conn, $start_date, $end_date);
        fputcsv($output, ['Total Users', $stats['total_users']]);
        fputcsv($output, ['Active Users', $stats['active_users']]);
        fputcsv($output, ['Pending Users', $stats['pending_users']]);
        fputcsv($output, ['Disabled Users', $stats['disabled_users']]);
    }
    
    fclose($output);
    exit();
}

// Get statistics based on report type
$stats = $report_type == 'projects' ? 
         getProjectStats($conn, $start_date, $end_date) : 
         getUserStats($conn, $start_date, $end_date);

// Get detailed project data if needed
$projects = $report_type == 'projects' ? 
           getProjectDetails($conn, $start_date, $end_date) : 
           null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Project Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">
            <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li class="nav-item"><a href="../includes/logout.php" class="nav-link">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Reports</h2>

        <div class="report-filters card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Report Type:</label>
                    <select name="report_type" onchange="this.form.submit()">
                        <option value="projects" <?php echo $report_type == 'projects' ? 'selected' : ''; ?>>Projects Report</option>
                        <option value="users" <?php echo $report_type == 'users' ? 'selected' : ''; ?>>Users Report</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date Range:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                    <span>to</span>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn-primary">Generate Report</button>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['download' => 'true'])); ?>" 
                   class="btn-secondary">Download CSV</a>
            </form>
        </div>

        <div class="report-content">
            <div class="stats-grid">
                <?php if ($report_type == 'projects'): ?>
                    <div class="stat-card">
                        <h3>Project Statistics</h3>
                        <canvas id="projectChart"></canvas>
                        <div class="stat-details">
                            <p>Total Projects: <?php echo $stats['total_projects']; ?></p>
                            <p>Pending: <?php echo $stats['pending_projects']; ?></p>
                            <p>Approved: <?php echo $stats['approved_projects']; ?></p>
                            <p>Rejected: <?php echo $stats['rejected_projects']; ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <h3>User Statistics</h3>
                        <canvas id="userChart"></canvas>
                        <div class="stat-details">
                            <p>Total Users: <?php echo $stats['total_users']; ?></p>
                            <p>Active: <?php echo $stats['active_users']; ?></p>
                            <p>Pending: <?php echo $stats['pending_users']; ?></p>
                            <p>Disabled: <?php echo $stats['disabled_users']; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($report_type == 'projects' && $projects): ?>
            <div class="card">
                <h3>Project Details</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Submitter</th>
                            <th>Institution</th>
                            <th>Status</th>
                            <th>Submission Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($project = mysqli_fetch_assoc($projects)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                            <td><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($project['institution']); ?></td>
                            <td><span class="status-badge <?php echo $project['status']; ?>"><?php echo ucfirst($project['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize charts based on report type
        <?php if ($report_type == 'projects'): ?>
        new Chart(document.getElementById('projectChart'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Approved', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo $stats['pending_projects']; ?>,
                        <?php echo $stats['approved_projects']; ?>,
                        <?php echo $stats['rejected_projects']; ?>
                    ],
                    backgroundColor: ['#ffd700', '#28a745', '#dc3545']
                }]
            }
        });
        <?php else: ?>
        new Chart(document.getElementById('userChart'), {
            type: 'pie',
            data: {
                labels: ['Active', 'Pending', 'Disabled'],
                datasets: [{
                    data: [
                        <?php echo $stats['active_users']; ?>,
                        <?php echo $stats['pending_users']; ?>,
                        <?php echo $stats['disabled_users']; ?>
                    ],
                    backgroundColor: ['#28a745', '#ffd700', '#dc3545']
                }]
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
