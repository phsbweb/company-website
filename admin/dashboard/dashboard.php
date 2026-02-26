<?php
include '../shared/auth.php';
include '../../user/profile_page/includes/db.php';

// Fetch stats with error handling
$projectsCount = 0;
$attendanceCount = 0;

try {
    $projectsCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

    // Connect to attendance DB for daily stats
    $att_host = 'localhost';
    $att_db   = 'phsb';
    $att_user = 'root';
    $att_pass = '';
    $att_dsn = "mysql:host=$att_host;dbname=$att_db;charset=utf8mb4";
    $att_pdo = new PDO($att_dsn, $att_user, $att_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $attendanceCount = $att_pdo->query("SELECT COUNT(*) FROM attendance WHERE DATE(created_at) = CURDATE()")->fetchColumn();
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
    <link rel="stylesheet" href="../shared/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

    <?php
    $activePage = 'dashboard';
    $baseUrl = '../';
    include '../shared/sidebar.php';
    ?>

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
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <h3>Checked In Today</h3>
                    <p><?php echo $attendanceCount; ?></p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>