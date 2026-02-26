<?php
include 'auth.php';
// Include the attendance database connection
// Assuming the db_connect.php in user/attendance is what we need
require_once '../user/attendance/db_connect.php';

// Handle Date Filter
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch attendance records for the selected date
try {
    $stmt = $pdo->prepare("
        SELECT a.*, e.full_name, e.username 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        WHERE DATE(a.created_at) = ? 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$selected_date]);
    $records = $stmt->fetchAll();
} catch (Exception $e) {
    $records = [];
    $error = "Failed to fetch attendance records: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Priority Horizon Admin</title>
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

        .admin-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }

        /* Filter Section */
        .filter-section {
            margin-bottom: 30px;
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-section input[type="date"] {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            outline: none;
            font-family: inherit;
        }

        .btn-filter {
            padding: 10px 20px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid var(--border-color);
            color: #737373;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-checked_in {
            background: #ecfdf5;
            color: #065f46;
        }

        .status-checked_out {
            background: #f1f5f9;
            color: #475569;
        }

        .location-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .location-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-logo">PHSB Admin</div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="projects.php"><i class="fas fa-project-diagram"></i> Projects</a></li>
            <li><a href="featured.php"><i class="fas fa-star"></i> Featured</a></li>
            <li><a href="attendance.php" class="active"><i class="fas fa-user-check"></i> Attendance</a></li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem;">Employee Attendance</h1>
                <p style="color: #737373;">View and filter attendance records</p>
            </div>
        </div>

        <div class="filter-section">
            <form action="attendance.php" method="GET" style="display: flex; align-items: center; gap: 15px;">
                <label for="date" style="font-weight: 600; font-size: 0.9rem; color: #525252;">Select Date:</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
                <button type="submit" class="btn-filter">Filter</button>
            </form>
            <div style="margin-left: auto;">
                <span style="font-size: 0.9rem; color: #737373;">
                    Displaying records for: <strong><?php echo date('M d, Y', strtotime($selected_date)); ?></strong>
                </span>
            </div>
        </div>

        <div class="admin-card">
            <?php if (isset($error)): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 8px; border: 1px solid #fee2e2;">
                    <?php echo $error; ?>
                </div>
            <?php elseif (empty($records)): ?>
                <div style="text-align: center; padding: 40px; color: #737373;">
                    <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No attendance records found for this date.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #171717;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div style="font-size: 0.8rem; color: #737373;">@<?php echo htmlspecialchars($row['username']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : '-'; ?></div>
                                    <?php if ($row['location_in']): ?>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row['location_in']); ?>" target="_blank" class="location-link">
                                            <i class="fas fa-map-marker-alt"></i> View Location
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '-'; ?></div>
                                    <?php if ($row['location_out']): ?>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row['location_out']); ?>" target="_blank" class="location-link">
                                            <i class="fas fa-map-marker-alt"></i> View Location
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>