<?php
/**
 * Shared Sidebar for Admin Panel
 * Include this file in all admin pages
 * 
 * Usage: require_once __DIR__ . '/sidebar.php';
 * Make sure $role and $admin variables are set before including
 */

// Get current page filename for active state
$current_page = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'staff';
?>
<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../images/ICSA-LOGO.png" alt="ICSA Logo">
                <h2><?php echo $role === 'admin' ? 'Admin' : 'User'; ?></h2>
            </div>
        </div>
        <nav class="nav">
            <a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
            <a href="inquiries.php" class="nav-item <?php echo $current_page === 'inquiries.php' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Inquiries</a>
            <a href="courses.php" class="nav-item <?php echo in_array($current_page, ['courses.php', 'course-edit.php']) ? 'active' : ''; ?>"><i class="fas fa-graduation-cap"></i> Courses</a>
            <?php if ($role === 'admin'): ?>
            <a href="users.php" class="nav-item <?php echo $current_page === 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a>
            <?php endif; ?>
            <a href="settings.php" class="nav-item <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="../index.html" class="website-link" target="_blank">
            <i class="fas fa-external-link-alt"></i> View Website
        </a>
    </div>
</aside>
