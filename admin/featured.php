<?php
include 'auth.php';
include '../user/profile_page/includes/db.php';

// Handle updating featured projects
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_projects = $_POST['featured'] ?? [];

    // Validate we only have up to 3
    if (count($selected_projects) > 3) {
        $error = "You can only select up to 3 featured projects.";
    } else {
        try {
            $pdo->beginTransaction();

            // Clear current featured projects
            $pdo->exec("DELETE FROM featured_projects");

            // Insert new ones
            $stmt = $pdo->prepare("INSERT INTO featured_projects (slot, project_id) VALUES (?, ?)");
            foreach ($selected_projects as $index => $project_id) {
                $slot = $index + 1;
                $stmt->execute([$slot, $project_id]);
            }

            $pdo->commit();
            $msg = "Featured projects updated successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to update featured projects: " . $e->getMessage();
        }
    }
}

// Fetch all projects for selection
$stmt = $pdo->query("SELECT id, title, client FROM projects ORDER BY id DESC");
$all_projects = $stmt->fetchAll();

// Fetch current featured project IDs
$stmt = $pdo->query("SELECT project_id FROM featured_projects");
$featured_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Projects - Priority Horizon Admin</title>
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

        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            height: 100vh;
            border-right: 1px solid var(--border-color);
            position: fixed;
            padding: 30px 20px;
        }

        .sidebar-logo {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 40px;
            padding: 0 10px;
        }

        .nav-links {
            list-style: none;
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

        .main-content {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 40px;
        }

        .admin-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            max-width: 1000px;
        }

        .project-list {
            margin-top: 20px;
        }

        .project-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .project-item:last-child {
            border-bottom: none;
        }

        .project-item input[type="checkbox"] {
            margin-right: 15px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .btn-save {
            background: var(--accent-color);
            color: #ffffff;
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fee2e2;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-logo">PHSB Admin</div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="projects.php"><i class="fas fa-project-diagram"></i> Projects</a></li>
            <li><a href="featured.php" class="active"><i class="fas fa-star"></i> Featured</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1 style="font-size: 1.75rem; margin-bottom: 10px;">Featured Projects</h1>
        <p style="color: #737373; margin-bottom: 30px;">Select exactly 3 projects to display on the homepage.</p>

        <div class="admin-card">
            <?php if (isset($msg)): ?>
                <div class="alert alert-success"><?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="featured.php" method="POST">
                <div class="project-list">
                    <?php foreach ($all_projects as $proj): ?>
                        <div class="project-item">
                            <input type="checkbox" name="featured[]" value="<?php echo $proj['id']; ?>"
                                <?php echo in_array($proj['id'], $featured_ids) ? 'checked' : ''; ?>
                                onclick="limitCheckboxes(this)">
                            <div>
                                <h4 style="font-size: 1rem; color: #171717;"><?php echo htmlspecialchars($proj['title']); ?></h4>
                                <p style="font-size: 0.85rem; color: #737373;"><?php echo htmlspecialchars($proj['client']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn-save">Update Selection</button>
            </form>
        </div>
    </div>

    <script>
        function limitCheckboxes(checkbox) {
            var checkboxes = document.getElementsByName('featured[]');
            var count = 0;
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) count++;
            }
            if (count > 3) {
                checkbox.checked = false;
                alert("You can only select up to 3 featured projects.");
            }
        }
    </script>
</body>

</html>