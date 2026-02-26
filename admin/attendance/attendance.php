<?php
include '../shared/auth.php';
// Include the attendance database connection
require_once '../../user/attendance/db_connect.php';

// Handle Actions (Manual Checkout, Edit Time)
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'manual_checkout') {
                $id = $_POST['attendance_id'];
                $now = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE attendance SET check_out = ?, status = 'checked_out' WHERE id = ?");
                $stmt->execute([$now, $id]);
                $message = "Employee checked out successfully.";
            } elseif ($_POST['action'] === 'edit_checkout_time') {
                $id = $_POST['attendance_id'];
                $new_time = $_POST['new_checkout_time'];
                $stmt = $pdo->prepare("UPDATE attendance SET check_out = ?, status = 'checked_out' WHERE id = ?");
                $stmt->execute([$new_time, $id]);
                $message = "Checkout time updated successfully.";
            }
        } catch (Exception $e) {
            $error = "Action failed: " . $e->getMessage();
        }
    }
}

// Handle Filters
$selected_date = isset($_GET['date']) ? $_GET['date'] : (isset($_GET['month']) ? '' : date('Y-m-d'));
$selected_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Fetch Employees for Filter
$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")->fetchAll();

// Fetch attendance records based on filters
try {
    $query = "SELECT a.*, e.full_name, e.username 
              FROM attendance a 
              JOIN employees e ON a.employee_id = e.id 
              WHERE 1=1";
    $params = [];

    if ($selected_date) {
        $query .= " AND DATE(a.created_at) = ?";
        $params[] = $selected_date;
    } elseif ($selected_month) {
        $query .= " AND DATE_FORMAT(a.created_at, '%Y-%m') = ?";
        $params[] = $selected_month;
    }

    if ($selected_employee) {
        $query .= " AND a.employee_id = ?";
        $params[] = $selected_employee;
    }

    $query .= " ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} catch (Exception $e) {
    $records = [];
    $error = "Failed to fetch attendance records: " . $e->getMessage();
}

// Calculate Monthly Hours if employee filter is active
$monthly_hours = null;
if ($selected_employee && $selected_month) {
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(TIMESTAMPDIFF(SECOND, check_in, check_out)) as total_seconds
            FROM attendance 
            WHERE employee_id = ? 
            AND DATE_FORMAT(created_at, '%Y-%m') = ?
            AND check_out IS NOT NULL
        ");
        $stmt->execute([$selected_employee, $selected_month]);
        $result = $stmt->fetch();
        if ($result && $result['total_seconds']) {
            $total_seconds = $result['total_seconds'];
            $h = floor($total_seconds / 3600);
            $m = floor(($total_seconds % 3600) / 60);
            $monthly_hours = "{$h}h {$m}m";
        } else {
            $monthly_hours = "0h 0m";
        }
    } catch (Exception $e) {
        $error = "Failed to calculate monthly hours: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Global CSS -->
    <link rel="stylesheet" href="../shared/style.css">
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: var(--accent-color);
            color: white;
            padding: 25px;
            border-radius: 12px;
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
            font-size: 0.8rem;
            opacity: 0.7;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.05em;
        }

        .summary-info p {
            font-size: 1.75rem;
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
    </style>
</head>

<body>

    <?php
    $activePage = 'attendance';
    $baseUrl = '../';
    include '../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Attendance Center</h1>
                <p style="color: #737373;">Filter logs, calculate hours, and manage checkouts</p>
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
                <div class="filter-group">
                    <label>Report Month</label>
                    <input type="month" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                </div>
            </form>
        </div>

        <?php
        $display_info = $selected_date ? date('M d, Y', strtotime($selected_date)) : date('F Y', strtotime($selected_month . '-01'));
        ?>
        <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
            <p style="font-size: 0.95rem; color: #525252;">
                Displaying: <strong style="color: var(--accent-color);"><?php echo $display_info; ?></strong>
                <?php if (!$selected_date): ?> <span style="font-size: 0.8rem; color: #737373;">(Month View)</span><?php endif; ?>
            </p>
            <?php if ($selected_date || $selected_employee): ?>
                <a href="attendance.php?date=" style="font-size: 0.85rem; color: #737373; text-decoration: none;"><i class="fas fa-times"></i> Clear Filters</a>
            <?php endif; ?>
        </div>

        <?php if ($monthly_hours !== null || $selected_employee): ?>
            <div class="summary-grid">
                <?php if ($monthly_hours !== null): ?>
                    <div class="summary-card">
                        <div class="summary-info">
                            <h4>Monthly Total</h4>
                            <p><?php echo $monthly_hours; ?></p>
                        </div>
                        <div style="font-size: 2.5rem; opacity: 0.2;"><i class="fas fa-history"></i></div>
                    </div>
                <?php endif; ?>

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
                            <th>Time In</th>
                            <th>Time Out</th>
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
                                </td>
                                <td>
                                    <?php if ($row['check_out']): ?>
                                        <div style="font-weight: 500;"><?php echo date('h:i A', strtotime($row['check_out'])); ?></div>
                                        <div style="font-size: 0.75rem; color: #737373;"><?php echo date('d M Y', strtotime($row['check_out'])); ?></div>
                                    <?php else: ?>
                                        <span style="color: var(--warning-color); font-weight: 600; font-size: 0.85rem;">Active Session</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo str_replace('_', ' ', strtoupper($row['status'])); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($row['status'] === 'checked_in'): ?>
                                        <form action="attendance.php" method="POST" style="display:inline;" onsubmit="return confirm('Force checkout this employee?');">
                                            <input type="hidden" name="action" value="manual_checkout">
                                            <input type="hidden" name="attendance_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="action-btn checkout">
                                                <i class="fas fa-sign-out-alt"></i> Force Checkout
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
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Update Record</div>
            <div class="modal-subheader">Adjust the checkout timestamp for this session.</div>
            <form action="attendance.php" method="POST">
                <input type="hidden" name="action" value="edit_checkout_time">
                <input type="hidden" name="attendance_id" id="modal_id">
                <div class="filter-group" style="margin-bottom: 25px;">
                    <label>Correct Checkout Time</label>
                    <input type="datetime-local" name="new_checkout_time" id="modal_time" required>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn-primary" style="background: #f5f5f5; color: #525252;" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Confirm Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, time) {
            document.getElementById('modal_id').value = id;
            document.getElementById('modal_time').value = time;
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