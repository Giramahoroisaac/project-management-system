<?php


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to access this page";
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/doc_project/login.php';
        if (strpos($redirect, 'login.php') === false) {
            $redirect = '/doc_project/login.php';
        }
        header("Location: " . $redirect);
        exit();
    }
    
    // Redirect sub_admin to admin dashboard
    if ($_SESSION['role'] === 'sub_admin' && strpos($_SERVER['PHP_SELF'], '/user/') !== false) {
        header("Location: /doc_project/admin/dashboard.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    $admin_roles = ['super_admin', 'sub_admin'];
    if (!in_array($_SESSION['role'], $admin_roles)) {
        $_SESSION['error'] = "Access denied";
        header("Location: ../user/dashboard.php");
        exit();
    }
}

function requireSuperAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'super_admin') {
        $_SESSION['error'] = "Access denied";
        header("Location: ../admin/dashboard.php");
        exit();
    }
}

function hasPermission($permission) {
    // Sub admin has all permissions except managing roles
    if ($_SESSION['role'] === 'sub_admin') {
        return $permission !== 'manage_roles';
    }
    
    switch($permission) {
        case 'manage_users':
        case 'approve_users':
        case 'review_projects':
        case 'manage_roles':
            return $_SESSION['role'] === 'super_admin';
        default:
            return false;
    }
}

function logAction($conn, $action, $description = '') {
    if (isset($_SESSION['user_id'])) {
        $sql = "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $action, $description);
        mysqli_stmt_execute($stmt);
    }
}
?>
