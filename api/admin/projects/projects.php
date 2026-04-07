<?php
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../../user/profile_page/includes/db.php';

$projects = [];
$error = false;

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
    <!-- Global CSS -->
    <link rel="stylesheet" href="../../../assets/admin/shared/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

    <?php
    $activePage = 'projects';
    $baseUrl = '../';
include __DIR__ . '/../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem;">Manage Projects</h1>
                <p style="color: #737373;">View and update your company portfolio</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="featured.php" class="btn-add" style="background: #737373;"><i class="fas fa-star"></i> Manage Featured</a>
                <a href="add-project.php" class="btn-add"><i class="fas fa-plus"></i> Add New Project</a>
            </div>
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
                            <td colspan="9" style="text-align: center; padding: 40px; color: #737373;">No projects found. <a href="add-project.php">Add your first project</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>
