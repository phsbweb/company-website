<?php
// Determine the base path relative to the current file
// Since pages are in subfolders (like admin/dashboard/ or admin/projects/), 
// we need to know how to get back to the admin root.
$baseUrl = $baseUrl ?? '../';
?>
<div class="sidebar">
    <div class="sidebar-logo">PHSB Admin</div>
    <ul class="nav-links">
        <li>
            <a href="<?php echo $baseUrl; ?>dashboard/dashboard.php" class="<?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>projects/projects.php" class="<?php echo ($activePage == 'projects') ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram"></i> Projects
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>projects/featured.php" class="<?php echo ($activePage == 'featured') ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Featured
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>attendance/attendance.php" class="<?php echo ($activePage == 'attendance') ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i> Attendance
            </a>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>create-user.php" class="<?php echo ($activePage == 'create-user') ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Create User
            </a>
        </li>
    </ul>
    <div class="logout-btn">
        <a href="<?php echo $baseUrl; ?>auth/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>