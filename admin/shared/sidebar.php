<?php
// Determine the base path relative to the current file
$baseUrl = $baseUrl ?? '../';

// Fetch pending leave count for notifications
require_once dirname(__DIR__, 2) . '/user/attendance/db_connect.php';
$stmt_pending = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
$pending_count = $stmt_pending->fetchColumn();
?>
<div class="sidebar">
    <div class="sidebar-logo-container">
        <div class="sidebar-logo">PHSB Admin</div>
        <?php if ($pending_count > 0): ?>
            <a href="<?php echo $baseUrl; ?>schedules/leaves.php" class="notification-bell" title="<?php echo $pending_count; ?> Pending Leave Requests">
                <i class="fas fa-bell"></i>
                <span class="bell-dot"></span>
            </a>
        <?php endif; ?>
    </div>
    <ul class="nav-links">
        <li>
            <a href="<?php echo $baseUrl; ?>dashboard/dashboard.php" class="<?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <li class="has-dropdown <?php echo ($activePage == 'employees' || $activePage == 'departments') ? 'active open' : ''; ?>">
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
                    <a href="<?php echo $baseUrl; ?>employees/departments.php" class="<?php echo ($activePage == 'departments') ? 'active' : ''; ?>">
                        Departments
                    </a>
                </li>
            </ul>
        </li>

        <li class="has-dropdown <?php echo ($activePage == 'attendance' || $activePage == 'leaves' || $activePage == 'holidays' || $activePage == 'calendar') ? 'active open' : ''; ?>">
            <a href="javascript:void(0)" class="dropdown-toggle">
                <i class="fas fa-calendar-alt"></i> Schedules
                <i class="fas fa-chevron-right chevron"></i>
            </a>
            <ul class="sub-menu">
                <li>
                    <a href="<?php echo $baseUrl; ?>schedules/attendance.php" class="<?php echo ($activePage == 'attendance') ? 'active' : ''; ?>">
                        Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>schedules/leaves.php" class="<?php echo ($activePage == 'leaves') ? 'active' : ''; ?>" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Leaves</span>
                        <?php if ($pending_count > 0): ?>
                            <span class="sidebar-badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>schedules/holidays.php" class="<?php echo ($activePage == 'holidays') ? 'active' : ''; ?>">
                        Holidays
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>schedules/calendar.php" class="<?php echo ($activePage == 'calendar') ? 'active' : ''; ?>">
                        Calendar
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
            <a href="<?php echo $baseUrl; ?>system/logs.php" class="<?php echo ($activePage == 'logs') ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> System Logs
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