<?php
include 'auth.php';
include '../user/profile_page/includes/db.php';

$projects = [];
$error = false;

// Month mapping for display
$months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

// Fetch projects from database with error handling
try {
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management - Priority Horizon Admin</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --accent-color: #171717;
            --bg-light: #fafafa;
            --border-color: #e5e5e5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-light);
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            height: 100vh;
            border-right: 1px solid var(--border-color);
            position: fixed;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 40px;
            padding: 0 10px;
        }

        .nav-links {
            list-style: none;
            flex-grow: 1;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #525252;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .nav-links a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .nav-links a.active,
        .nav-links a:hover {
            background: #f5f5f5;
            color: var(--accent-color);
        }

        .logout-btn {
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .admin-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }

        .btn-add {
            background: var(--accent-color);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
        }

        .btn-add i {
            margin-right: 8px;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 1000px;
        }

        .data-table th {
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            color: #737373;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
            color: #171717;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-ongoing {
            background: #fef9c3;
            color: #854d0e;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #f5f5f5;
            color: #525252;
        }

        .btn-edit:hover {
            background: #e5e5e5;
        }

        .btn-delete {
            background: #fef2f2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #fee2e2;
        }

        .vo-tick {
            color: #166534;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-logo">PHSB Admin</div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="projects.php" class="active"><i class="fas fa-project-diagram"></i> Projects</a></li>
            <li><a href="featured.php"><i class="fas fa-star"></i> Featured</a></li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php" style="color: #991b1b; text-decoration: none; display: flex; align-items: center; padding: 12px 15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem;">Manage Projects</h1>
                <p style="color: #737373;">View and update your company portfolio</p>
            </div>
            <a href="add-project.php" class="btn-add"><i class="fas fa-plus"></i> Add New Project</a>
        </div>

        <?php if ($error): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fee2e2;">
                <strong>Database Error:</strong> <?php echo $error; ?>. Please check your database settings in <code>includes/db.php</code>.
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Project Title</th>
                        <th>Client</th>
                        <th>Contract Sum (RM)</th>
                        <th>Completion</th>
                        <th>Status</th>
                        <th>EOT Date</th>
                        <th>EOT No.</th>
                        <th>VO</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($projects) > 0): ?>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($project['id']); ?></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($project['title']); ?></td>
                                <td><?php echo htmlspecialchars($project['client']); ?></td>
                                <td><?php echo number_format($project['contract_sum'], 2); ?></td>
                                <td><?php echo htmlspecialchars(($months[$project['completion_month']] ?? '') . ' ' . $project['completion_year']); ?></td>
                                <td>
                                    <?php if ($project['status'] == 1): ?>
                                        <span class="status-badge status-completed">Completed</span>
                                    <?php else: ?>
                                        <span class="status-badge status-ongoing">Ongoing</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($project['eot_month'] && $project['eot_year']) {
                                        echo htmlspecialchars($months[$project['eot_month']] . ' ' . $project['eot_year']);
                                    } else {
                                        echo "-";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($project['eot_number'] === 0 || $project['eot_number'] === '0') {
                                        echo "-";
                                    } else {
                                        echo htmlspecialchars($project['eot_number'] ?? "-");
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($project['vo'] == 1): ?>
                                        <span class="vo-tick"><i class="fas fa-check"></i></span>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete-project.php?id=<?php echo $project['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #737373;">No projects found. <a href="add-project.php">Add your first project</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>