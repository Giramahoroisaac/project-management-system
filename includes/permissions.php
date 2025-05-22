<?php
// Role and Permission Management

function getUserPermissions($role) {
    $permissions = [
        'super_admin' => [
            'manage_users' => true,
            'reset_passwords' => true,
            'approve_users' => true,
            'review_projects' => true,
            'manage_roles' => true,
            'view_audit_logs' => true,
            'generate_reports' => true
        ],
        'sub_admin' => [
            'manage_users' => true,
            'reset_passwords' => true,
            'approve_users' => true,
            'review_projects' => true,
            'view_audit_logs' => true,
            'generate_reports' => true
        ],
        'user_manager' => [
            'manage_users' => true,
            'reset_passwords' => true
        ],
        'approver' => [
            'approve_users' => true
        ],
        'reviewer' => [
            'review_projects' => true
        ],
        'user' => []
    ];

    return isset($permissions[$role]) ? $permissions[$role] : [];
}

function hasPermission($requiredPermission) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    $userPermissions = getUserPermissions($_SESSION['user_role']);
    return isset($userPermissions[$requiredPermission]) && $userPermissions[$requiredPermission] === true;
}

function requirePermission($permission) {
    if (!hasPermission($permission)) {
        $_SESSION['error'] = "Access denied. You don't have permission to perform this action.";
        header('Location: ../dashboard.php');
        exit();
    }
}

// Function to check multiple permissions (ANY)
function hasAnyPermission($permissions) {
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

// Function to check multiple permissions (ALL)
function hasAllPermissions($permissions) {
    foreach ($permissions as $permission) {
        if (!hasPermission($permission)) {
            return false;
        }
    }
    return true;
}

// Get all available roles for assignment
function getAvailableRoles($currentUserRole) {
    $roles = [
        'super_admin' => 'Super Administrator',
        'sub_admin' => 'Sub Administrator',
        'user_manager' => 'User Manager',
        'approver' => 'User Approver',
        'reviewer' => 'Project Reviewer',
        'user' => 'Regular User'
    ];

    // Super admin can assign any role
    if ($currentUserRole === 'super_admin') {
        return $roles;
    }

    // Sub admin can't assign super_admin or sub_admin roles
    if ($currentUserRole === 'sub_admin') {
        unset($roles['super_admin']);
        unset($roles['sub_admin']);
        return $roles;
    }

    // Others can only see their own role
    return ['user' => 'Regular User'];
}

// Get role display name
function getRoleDisplayName($role) {
    $roleNames = [
        'super_admin' => 'Super Administrator',
        'sub_admin' => 'Sub Administrator',
        'user_manager' => 'User Manager',
        'approver' => 'User Approver',
        'reviewer' => 'Project Reviewer',
        'user' => 'Regular User'
    ];
    
    return isset($roleNames[$role]) ? $roleNames[$role] : 'Unknown Role';
}
?>
