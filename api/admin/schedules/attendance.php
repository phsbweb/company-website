<?php
require_once __DIR__ . '/../shared/auth.php';
// Include the attendance database connection
require_once __DIR__ . '/../../user/attendance/db_connect.php';

// Handle Actions (Manual Checkout, Edit Time)
$message = $_SESSION['success_msg'] ?? "";
$error = $_SESSION['error_msg'] ?? "";
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_url = "attendance.php" . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');

    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'manual_checkout') {
                $id = $_POST['attendance_id'];
                $now = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE attendance SET check_out = ?, status = 'checked_out' WHERE id = ?");
                $stmt->execute([$now, $id]);
                $_SESSION['success_msg'] = "Employee checked out successfully.";
            } elseif ($_POST['action'] === 'edit_checkout_time') {
                $id = $_POST['attendance_id'];
                $new_date = $_POST['new_checkout_date'];
                $new_time = $_POST['new_checkout_time'];
                $combined_datetime = $new_date . ' ' . $new_time . ':00';
                $stmt = $pdo->prepare("UPDATE attendance SET check_out = ?, status = 'checked_out' WHERE id = ?");
                $stmt->execute([$combined_datetime, $id]);
                $_SESSION['success_msg'] = "Checkout time updated successfully.";
            }

            header("Location: $redirect_url");
            exit;
        } catch (Exception $e) {
            $_SESSION['error_msg'] = "Action failed: " . $e->getMessage();
            header("Location: $redirect_url");
            exit;
        }
    }
}

// Handle Filters
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$selected_date_start = null;
$selected_date_end = null;

if (!empty($selected_date)) {
    $selected_date_start = $selected_date . ' 00:00:00';
    $selected_date_end = date('Y-m-d H:i:s', strtotime($selected_date . ' +1 day'));
}

// Fetch Employees for Filter
$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")->fetchAll();
$default_employee_id = count($employees) > 0 ? $employees[0]['id'] : '';

// Pagination Settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch attendance records based on filters
try {
    // First, count total records for pagination
    $count_query = "SELECT COUNT(*) FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE 1=1";
    $params = [];

    if ($selected_date_start && $selected_date_end) {
        $count_query .= " AND a.created_at >= ? AND a.created_at < ?";
        $params[] = $selected_date_start;
        $params[] = $selected_date_end;
    }

    if ($selected_employee) {
        $count_query .= " AND a.employee_id = ?";
        $params[] = $selected_employee;
    }

    $stmt_count = $pdo->prepare($count_query);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Now fetch the records for the current page
    $query = "SELECT a.*, e.full_name, e.username, e.working_shift 
              FROM attendance a 
              JOIN employees e ON a.employee_id = e.id 
              WHERE 1=1";

    // Reuse filter conditions
    if ($selected_date_start && $selected_date_end) {
        $query .= " AND a.created_at >= ? AND a.created_at < ?";
    }

    if ($selected_employee) {
        $query .= " AND a.employee_id = ?";
    }

    $query .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";

    $records_params = array_merge($params, [$limit, $offset]);

    $stmt = $pdo->prepare($query);
    $stmt->execute($records_params);
    $records = $stmt->fetchAll();
} catch (Exception $e) {
    $records = [];
    $total_records = 0;
    $total_pages = 0;
    $error = "Failed to fetch attendance records: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Global CSS -->
    <link rel="stylesheet" href="../../../assets/admin/shared/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Page-specific overrides or additional styles */
        .filter-panel {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #525252;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            outline: none;
            font-size: 0.9rem;
            background: #fff;
        }

        /* Summary Info */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: var(--accent-color);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .summary-card.light {
            background: #fff;
            color: var(--accent-color);
            border: 1px solid var(--border-color);
        }

        .summary-info h4 {
            font-size: 0.75rem;
            opacity: 0.7;
            text-transform: uppercase;
            margin-bottom: 3px;
            letter-spacing: 0.05em;
        }

        .summary-info p {
            font-size: 1.4rem;
            font-weight: 800;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid var(--border-color);
            color: #737373;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
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

        .late-badge {
            background: #fef2f2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            margin-top: 4px;
            display: inline-block;
            border: 1px solid #fee2e2;
            text-transform: uppercase;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn.edit {
            color: #525252;
            background: #f5f5f5;
        }

        .action-btn.edit:hover {
            background: #e5e5e5;
        }

        .action-btn.checkout {
            color: var(--warning-color);
            background: #fef3c7;
        }

        .action-btn.checkout:hover {
            background: #fde68a;
        }

        .modal-header {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--accent-color);
        }

        .modal-subheader {
            font-size: 0.9rem;
            color: #737373;
            margin-bottom: 25px;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .pagination-link {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: #525252;
            font-size: 0.9rem;
            font-weight: 600;
            background: #fff;
            transition: all 0.2s;
        }

        .pagination-link:hover {
            background: #f5f5f5;
            border-color: #d4d4d4;
        }

        .pagination-link.active {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        .pagination-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Location Styling */
        .location-link {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #525252;
            text-decoration: none;
            transition: color 0.2s;
            line-height: 1.3;
            white-space: normal;
            vertical-align: middle;
        }

        .location-link:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <?php
    $activePage = 'attendance';
    $baseUrl = '../';
    include __DIR__ . '/../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Attendance Center</h1>
                <p style="color: #737373;">Filter logs and manage checkouts</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="attendance-report.php<?php echo '?employee_id=' . ($selected_employee ?: $default_employee_id); ?>"
                    class="btn-primary" style="background: #2563eb;">
                    <i class="fas fa-file-invoice"></i> Attendance Report
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="filter-panel">
            <form action="attendance.php" method="GET" class="filter-form">
                <div class="filter-group">
                    <label>View Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
                </div>
                <div class="filter-group">
                    <label>Specific Employee</label>
                    <select name="employee_id">
                        <option value="">All Employees</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" <?php echo ($selected_employee == $emp['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                </div>
            </form>
        </div>

        <?php
        if ($selected_date) {
            $display_info = date('M d, Y', strtotime($selected_date));
        } else {
            $display_info = "All History";
        }
        ?>
        <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
            <p style="font-size: 0.95rem; color: #525252;">
                Displaying: <strong style="color: var(--accent-color);"><?php echo $display_info; ?></strong>
            </p>
            <?php if ($selected_date || $selected_employee): ?>
                <a href="attendance.php?date=&employee_id=" style="font-size: 0.85rem; color: #737373; text-decoration: none;"><i class="fas fa-times"></i> Clear Filters</a>
            <?php endif; ?>
        </div>

        <?php if ($selected_employee): ?>
            <div class="summary-grid">
                <div class="summary-card light">
                    <div class="summary-info">
                        <h4>Effective Rate</h4>
                        <p><?php echo count($records); ?> Sessions</p>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.1;"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <?php if (empty($records)): ?>
                <div style="text-align: center; padding: 60px; color: #737373;">
                    <i class="fas fa-search" style="font-size: 3.5rem; margin-bottom: 20px; opacity: 0.2;"></i>
                    <p style="font-weight: 500;">No attendance records matching your criteria.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>In Location</th>
                            <th>Out Location</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #171717;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div style="font-size: 0.8rem; color: #737373;">ID: #<?php echo str_pad($row['employee_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo date('h:i A', strtotime($row['check_in'])); ?></div>
                                    <div style="font-size: 0.75rem; color: #737373;"><?php echo date('d M Y', strtotime($row['check_in'])); ?></div>
                                    <?php if (($row['is_late'] ?? 0) == 1): ?>
                                        <span class="late-badge">Late</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['check_out']): ?>
                                        <div style="font-weight: 500;"><?php echo date('h:i A', strtotime($row['check_out'])); ?></div>
                                        <div style="font-size: 0.75rem; color: #737373;"><?php echo date('d M Y', strtotime($row['check_out'])); ?></div>
                                    <?php else: ?>
                                        <span style="color: var(--warning-color); font-weight: 600; font-size: 0.85rem;">Active Session</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <?php if (!empty($row['location_in'])): ?>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row['location_in']); ?>"
                                            target="_blank"
                                            class="location-link"
                                            title="<?php echo htmlspecialchars($row['location_in']); ?>">
                                            <i class="fas fa-location-dot" style="font-size: 0.7rem; opacity: 0.5; margin-right: 4px;"></i><?php echo htmlspecialchars($row['location_in']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #a3a3a3;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <?php if (!empty($row['location_out'])): ?>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row['location_out']); ?>"
                                            target="_blank"
                                            class="location-link"
                                            title="<?php echo htmlspecialchars($row['location_out']); ?>">
                                            <i class="fas fa-location-dot" style="font-size: 0.7rem; opacity: 0.5; margin-right: 4px;"></i><?php echo htmlspecialchars($row['location_out']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #a3a3a3;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo str_replace('_', ' ', strtoupper($row['status'])); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($row['status'] === 'checked_in'): ?>
                                        <form action="attendance.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars((string) $_SERVER['QUERY_STRING']) : ''; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Force checkout this employee?');">
                                            <input type="hidden" name="action" value="manual_checkout">
                                            <input type="hidden" name="attendance_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="action-btn checkout">
                                                Force Checkout
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="action-btn edit" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo date('Y-m-d\TH:i', strtotime($row['check_out'])); ?>')">
                                            <i class="fas fa-clock"></i> Edit Time
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Preserve filter parameters in pagination links
                        $filter_params = $_GET;
                        unset($filter_params['page']);
                        $query_string = http_build_query($filter_params);
                        $base_link = "attendance.php?" . ($query_string ? $query_string . "&" : "");
                        ?>

                        <a href="<?php echo $base_link; ?>page=<?php echo max(1, $page - 1); ?>"
                            class="pagination-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>

                        <?php
                        // Show up to 5 page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="<?php echo $base_link; ?>page=<?php echo $i; ?>"
                                class="pagination-link <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <a href="<?php echo $base_link; ?>page=<?php echo min($total_pages, $page + 1); ?>"
                            class="pagination-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Update Record</div>
            <div class="modal-subheader">Adjust the checkout timestamp for this session.</div>
            <form action="attendance.php?<?php echo htmlspecialchars($_SERVER['QUERY_STRING']); ?>" method="POST">
                <input type="hidden" name="action" value="edit_checkout_time">
                <input type="hidden" name="attendance_id" id="modal_id">
                <input type="hidden" name="new_checkout_date" id="modal_date_hidden">
                <div class="filter-group" style="margin-bottom: 25px;">
                    <label>Record Date</label>
                    <input type="text" id="modal_date_display" readonly style="background: #f9f9f9; border-color: #ddd; color: #666;">
                </div>
                <div class="filter-group" style="margin-bottom: 25px;">
                    <label>Correct Checkout Time</label>
                    <input type="time" name="new_checkout_time" id="modal_time" required>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn-primary" style="background: #f5f5f5; color: #525252;" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Confirm Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, datetime) {
            // datetime is in 'YYYY-MM-DDTHH:MM' format
            const parts = datetime.split('T');
            document.getElementById('modal_id').value = id;
            document.getElementById('modal_date_hidden').value = parts[0];
            document.getElementById('modal_date_display').value = parts[0];
            document.getElementById('modal_time').value = parts[1];
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeModal();
            }
        }
    </script>

</body>

</html>
