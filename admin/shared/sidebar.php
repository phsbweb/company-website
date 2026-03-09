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
        <li class="has-dropdown <?php echo ($activePage == 'employees' || $activePage == 'attendance' || $activePage == 'leaves' || $activePage == 'departments') ? 'active open' : ''; ?>">
            <a href="javascript:void(0)" class="dropdown-toggle">
                <i class="fas fa-users"></i> Employees
                <i class="fas fa-chevron-right chevron"></i>
            </a>
            <ul class="sub-menu">
                <li>
                    <a href="<?php echo $baseUrl; ?>employees/employees.php" class="<?php echo ($activePage == 'employees') ? 'active' : ''; ?>">
                        List
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>employees/attendance.php" class="<?php echo ($activePage == 'attendance') ? 'active' : ''; ?>">
                        Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>employees/leaves.php" class="<?php echo ($activePage == 'leaves') ? 'active' : ''; ?>">
                        Leaves
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>employees/departments.php" class="<?php echo ($activePage == 'departments') ? 'active' : ''; ?>">
                        Departments
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="<?php echo $baseUrl; ?>projects/projects.php" class="<?php echo ($activePage == 'projects') ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram"></i> Projects
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdowns = document.querySelectorAll('.has-dropdown');

        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const isOpen = dropdown.classList.contains('open');

                // Close others
                dropdowns.forEach(d => {
                    if (d !== dropdown) d.classList.remove('open');
                });

                if (isOpen) {
                    dropdown.classList.remove('open');
                } else {
                    dropdown.classList.add('open');
                }
            });
        });
    });
</script>