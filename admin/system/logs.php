<?php
include '../shared/auth.php';
require_once '../../user/attendance/db_connect.php';

// Pagination
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch Logs
$stmt = $pdo->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Total for pagination
$total = $pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin</title>
    <link rel="stylesheet" href="../shared/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .log-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .log-table th,
        .log-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .log-table th {
            background: #f8fafc;
            font-weight: 700;
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .log-table tr:last-child td {
            border-bottom: none;
        }

        .log-action-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            background: #f1f5f9;
            color: #475569;
        }

        .action-login {
            background: #dcfce7;
            color: #166534;
        }

        .action-edit {
            background: #eff6ff;
            color: #1e40af;
        }

        .action-approved {
            background: #dcfce7;
            color: #166534;
        }

        .action-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-link {
            padding: 8px 16px;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: #525252;
            font-weight: 600;
            transition: all 0.2s;
        }

        .page-link.active {
            background: var(--accent-color);
            color: #fff;
            border-color: var(--accent-color);
        }

        .page-link:hover:not(.active) {
            background: #f5f5f5;
        }
    </style>
</head>

<body>
    <?php
    $activePage = 'logs';
    $baseUrl = '../';
    include '../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">System Logs</h1>
                <p style="color: #737373;">Audit trail of administrative actions</p>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <table class="log-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Administrator</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">No logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log):
                            $actionClass = '';
                            if (stripos($log['action'], 'Login') !== false) $actionClass = 'action-login';
                            elseif (stripos($log['action'], 'Edit') !== false) $actionClass = 'action-edit';
                            elseif (stripos($log['action'], 'Approved') !== false) $actionClass = 'action-approved';
                            elseif (stripos($log['action'], 'Rejected') !== false) $actionClass = 'action-rejected';
                        ?>
                            <tr>
                                <td style="font-size: 0.85rem; color: #64748b;">
                                    <?php echo date('d M Y, H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($log['admin_username']); ?></div>
                                    <div style="font-size: 0.7rem; color: #94a3b8;">ID: #<?php echo $log['admin_id']; ?></div>
                                </td>
                                <td>
                                    <span class="log-action-badge <?php echo $actionClass; ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <?php if ($log['target_type']): ?>
                                        <span style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($log['target_type']); ?>:</span>
                                        #<?php echo $log['target_id']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.85rem; color: #475569; max-width: 400px;">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>