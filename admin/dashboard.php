<?php
include 'auth.php';
include '../user/profile_page/includes/db.php';

// Fetch stats with error handling
$projectsCount = 0;

try {
    $projectsCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
} catch (Exception $e) {
    // Keep 0 if failed
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Priority Horizon Admin</title>
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

        .logout-btn a {
            color: #991b1b;
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

        .welcome-box {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #f5f5f5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.25rem;
            color: var(--accent-color);
        }

        .stat-info h3 {
            font-size: 0.85rem;
            color: #737373;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-info p {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-color);
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-logo">PHSB Admin</div>
        <ul class="nav-links">
            <li><a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="projects.php"><i class="fas fa-project-diagram"></i> Projects</a></li>
            <li><a href="featured.php"><i class="fas fa-star"></i> Featured</a></li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem;">Dashboard Overview</h1>
                <p style="color: #737373;">Welcome back, Administrator</p>
            </div>
        </div>

        <div class="welcome-box">
            <h2 style="margin-bottom: 10px;">Hello, Admin!</h2>
            <p style="color: #737373; max-width: 600px;">This is your company profile management system. From here you can manage your projects, and update company information.</p>

            <?php if (!isset($pdo)): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid #fee2e2;">
                    <strong>Database Connection Error:</strong> Unable to connect to the database. Please check your credentials in <code>includes/db.php</code>.
                </div>
            <?php endif; ?>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-info">
                    <h3>Total Projects</h3>
                    <p><?php echo $projectsCount; ?></p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>