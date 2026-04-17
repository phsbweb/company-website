<?php
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../../user/profile_page/includes/db.php';
require_once __DIR__ . '/../../user/attendance/db_connect.php';

// Fetch stats with error handling
$projectsCount = 0;
$attendanceCount = 0;

try {
    $projectsCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $attendancePdo = attendanceDb();

    $todayStart = date('Y-m-d 00:00:00');
    $tomorrowStart = date('Y-m-d 00:00:00', strtotime('+1 day'));

    $stmt_count = $attendancePdo->prepare("SELECT COUNT(*) FROM attendance WHERE created_at >= ? AND created_at < ?");
    $stmt_count->execute([$todayStart, $tomorrowStart]);
    $attendanceCount = $stmt_count->fetchColumn();

    // Fetch Who's In Now (Active Sessions)
    $stmt_active = $attendancePdo->prepare("
        SELECT a.*, e.full_name, d.name as dept_name 
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE a.status = 'checked_in' AND a.check_in >= ? AND a.check_in < ?
        ORDER BY a.check_in DESC
    ");
    $stmt_active->execute([$todayStart, $tomorrowStart]);
    $active_sessions = $stmt_active->fetchAll();
} catch (Exception $e) {
    $active_sessions = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Priority Horizon Admin</title>
    <link rel="stylesheet" href="../../../assets/admin/shared/style.css">
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

        /* Who's In Now Widget */
        .active-sessions-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-top: 30px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ef4444;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .session-list {
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .session-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.2s;
        }

        .session-item:last-child {
            border-bottom: none;
        }

        .session-item:hover {
            background: #fafafa;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .session-details {
            flex-grow: 1;
        }

        .session-details .name {
            font-weight: 600;
            font-size: 0.95rem;
            display: block;
        }

        .session-details .dept {
            font-size: 0.75rem;
            color: #737373;
        }

        .session-time {
            text-align: right;
            margin-right: 20px;
        }

        .session-time .time {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .session-time .label {
            font-size: 0.7rem;
            color: #737373;
            text-transform: uppercase;
        }

        .location-link {
            padding: 8px;
            background: #f5f5f5;
            color: #737373;
            border-radius: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .location-link:hover {
            color: var(--accent-color);
        }

        .location-text {
            display: block;
            font-size: 0.75rem;
            color: #737373;
            margin-top: 4px;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-decoration: none;
        }

        .location-text:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <?php
    $activePage = 'dashboard';
    $baseUrl = '../';
include __DIR__ . '/../shared/sidebar.php';
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

        <!-- Who's In Now Widget -->
        <div class="active-sessions-card">
            <div class="card-header">
                <h3>Who's In Now</h3>
                <div class="live-indicator">
                    <span class="live-dot"></span>
                    Live
                </div>
            </div>
            <div class="session-list">
                <?php if (empty($active_sessions)): ?>
                    <div style="padding: 40px; text-align: center; color: #737373;">
                        <i class="fas fa-users-slash" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.3;"></i>
                        <p>No active sessions found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_sessions as $session): ?>
                        <div class="session-item">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($session['full_name'], 0, 1)); ?>
                            </div>
                            <div class="session-details">
                                <span class="name"><?php echo htmlspecialchars($session['full_name']); ?></span>
                                <span class="dept"><?php echo htmlspecialchars($session['dept_name'] ?? 'No Department'); ?></span>
                            </div>
                            <div class="session-time">
                                <span class="time"><?php echo date('H:i', strtotime($session['check_in'])); ?></span>
                                <span class="label">Checked In</span>
                            </div>
                            <div style="flex-shrink: 0;">
                                <?php if ($session['location_in']): ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($session['location_in']); ?>"
                                        target="_blank" class="location-text" title="<?php echo htmlspecialchars($session['location_in']); ?>">
                                        <i class="fas fa-location-dot" style="margin-right: 5px; opacity: 0.5;"></i>
                                        <?php echo htmlspecialchars($session['location_in']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="location-text" style="opacity: 0.3;">
                                        <i class="fas fa-location-dot" style="margin-right: 5px;"></i> No location
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>

</html>
