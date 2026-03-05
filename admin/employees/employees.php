<?php
include '../shared/auth.php';
require_once '../../user/attendance/db_connect.php';

// Handle Search and Pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_dept = isset($_GET['department_id']) ? $_GET['department_id'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch Departments for Filter
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

try {
    // Count total for pagination
    $count_query = "SELECT COUNT(*) FROM employees WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $count_query .= " AND (full_name LIKE ? OR username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (!empty($selected_dept)) {
        $count_query .= " AND department_id = ?";
        $params[] = $selected_dept;
    }

    $stmt_count = $pdo->prepare($count_query);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Fetch employees
    $query = "SELECT e.*, d.name as department_name 
              FROM employees e 
              LEFT JOIN departments d ON e.department_id = d.id 
              WHERE 1=1";
    if (!empty($search)) {
        $query .= " AND (full_name LIKE ? OR username LIKE ?)";
    }
    if (!empty($selected_dept)) {
        $query .= " AND e.department_id = ?";
    }
    $query .= " ORDER BY full_name ASC LIMIT ? OFFSET ?";

    $records_params = array_merge($params, [$limit, $offset]);
    $stmt = $pdo->prepare($query);
    $stmt->execute($records_params);
    $employees_list = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

$message = $_SESSION['success_msg'] ?? "";
$error = $error ?? ($_SESSION['error_msg'] ?? "");
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - PHSB Admin</title>
    <link rel="stylesheet" href="../shared/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php
    $activePage = 'employees';
    $baseUrl = '../';
    include '../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Employee Management</h1>
                <p style="color: #737373;">Manage staff records,shifts, and access</p>
            </div>
            <a href="add-employee.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add Employee
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-card" style="padding: 20px; margin-bottom: 20px;">
            <form action="employees.php" method="GET" style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="search" placeholder="Search name or username..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="flex-grow: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">

                <select name="department_id" style="padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; min-width: 150px;">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo ($selected_dept == $dept['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-primary" style="padding: 10px 20px;">
                    <i class="fas fa-search"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($selected_dept)): ?>
                    <a href="employees.php" class="btn-primary" style="background: #737373; padding: 10px 20px;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-card">
            <?php if (empty($employees_list)): ?>
                <div style="text-align: center; padding: 60px; color: #737373;">
                    <i class="fas fa-users-slash" style="font-size: 3.5rem; margin-bottom: 20px; opacity: 0.2;"></i>
                    <p style="font-weight: 500;">No employees found.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Department</th>
                            <th>Default Shift</th>
                            <th>Date Joined</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees_list as $emp): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #171717;"><?php echo htmlspecialchars($emp['full_name']); ?></div>
                                    <div style="font-size: 0.8rem; color: #737373;">ID: #<?php echo str_pad($emp['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($emp['username']); ?></td>
                                <td>
                                    <?php if ($emp['department_name']): ?>
                                        <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;">
                                            <?php echo htmlspecialchars($emp['department_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #a3a3a3; font-style: italic; font-size: 0.85rem;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: #f3f4f6; color: #4b5563;">
                                        <?php echo $emp['working_shift'] === '830-530' ? '8:30 - 5:30' : '8:00 - 5:00'; ?>
                                    </span>
                                </td>
                                <td style="color: #737373; font-size: 0.9rem;">
                                    <?php echo date('d M Y', strtotime($emp['created_at'])); ?>
                                </td>
                                <td style="text-align: center;">
                                    <div class="actions-dropdown">
                                        <button class="action-dot-btn" onclick="toggleDropdown(this, event)">
                                            <i class="fas fa-bars"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="edit-employee.php?id=<?php echo $emp['id']; ?>" class="dropdown-item">
                                                <i class="fas fa-edit" style="color: #2563eb;"></i> Edit
                                            </a>
                                            <form action="delete-employee.php" method="POST" style="display:contents;"
                                                onsubmit="return confirm('Are you sure you want to delete this employee? This will also remove their attendance history.');">
                                                <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                                                <button type="submit" class="dropdown-item delete">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="employees.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                                class="pagination-link <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #737373;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: white;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .action-btn.edit {
            color: #2563eb;
            background: #eff6ff;
            border-color: #dbeafe;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination-link {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            color: #525252;
            font-size: 0.9rem;
        }

        .pagination-link.active {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        /* Dropdown Actions Menu - Moved here for reliability */
        .actions-dropdown {
            position: relative;
            display: inline-block;
        }

        .action-dot-btn {
            background: #f5f5f5;
            border: 1px solid var(--border-color);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #525252;
            transition: all 0.2s;
        }

        .action-dot-btn:hover {
            background: #eeeeee;
            color: var(--accent-color);
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            min-width: 140px;
            display: none;
            overflow: hidden;
        }

        .dropdown-menu.show {
            display: block;
            animation: fadeInScale 0.15s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            color: #525252;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: background 0.2s;
            border: none;
            width: 100%;
            text-align: left;
            background: none;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background: #f8fafc;
            color: var(--accent-color);
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
            font-size: 0.9rem;
        }

        .dropdown-item.delete {
            color: var(--danger-color);
        }

        .dropdown-item.delete:hover {
            background: #fef2f2;
        }
    </style>
    <script>
        function toggleDropdown(btn, event) {
            event.stopPropagation();
            const menu = btn.nextElementSibling;

            // Close other menus
            document.querySelectorAll('.dropdown-menu').forEach(i => {
                if (i !== menu) i.classList.remove('show');
            });

            menu.classList.toggle('show');
        }

        // Close on outside click
        window.addEventListener('click', function() {
            document.querySelectorAll('.dropdown-menu').forEach(i => i.classList.remove('show'));
        });
    </script>
</body>

</html>