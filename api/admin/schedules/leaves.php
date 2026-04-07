<?php
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../../user/attendance/db_connect.php';

$message = $_SESSION['success_msg'] ?? "";
$error = $_SESSION['error_msg'] ?? "";
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Handle Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $leave_id = $_POST['leave_id'];
        $status = $_POST['status'];

        if (!in_array($status, ['approved', 'rejected'])) {
            throw new Exception("Invalid status.");
        }

        $stmt = $pdo->prepare("UPDATE leaves SET status = ? WHERE id = ?");
        $stmt->execute([$status, $leave_id]);

        // Log Change
require_once __DIR__ . '/../shared/logger.php';
        logAction($pdo, $_SESSION['admin_user_id'], $_SESSION['admin_username'], ucfirst($status) . ' Leave', 'Leave', $leave_id, "Leave request status changed to $status");

        $_SESSION['success_msg'] = "Leave request " . ucfirst($status) . " successfully.";
        header("Location: leaves.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Action failed: " . $e->getMessage();
        header("Location: leaves.php");
        exit;
    }
}

// Get filters from URL
$search = $_GET['search'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';
$active_tab = $_GET['tab'] ?? 'pending';

// Build Query with Filters
$query = "SELECT l.*, e.full_name, e.username 
          FROM leaves l 
          JOIN employees e ON l.employee_id = e.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (e.full_name LIKE ? OR e.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($month) && $month !== 'all') {
    $query .= " AND MONTH(l.start_date) = ?";
    $params[] = $month;
}

if (!empty($year) && $year !== 'all') {
    $query .= " AND YEAR(l.start_date) = ?";
    $params[] = $year;
}

$query .= " ORDER BY l.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$all_leaves = $stmt->fetchAll();

// Categorize leaves
$ongoing = [];
$pending = [];
$approved = [];
$rejected = [];
$cancelled = [];
$today = date('Y-m-d');

foreach ($all_leaves as $leave) {
    // Normalize status for comparison
    $status = trim(strtolower($leave['status']));
    if ($status === '') $status = 'cancelled'; // Map blank to cancelled per user
    
    $endTime = !empty($leave['end_date']) ? strtotime($leave['end_date']) : 0;
    $todayTime = strtotime($today);

    if ($status === 'approved' && $todayTime <= $endTime) {
        $ongoing[] = $leave;
    } elseif ($status === 'pending') {
        $pending[] = $leave;
    } elseif ($status === 'approved' && $todayTime > $endTime) {
        $approved[] = $leave;
    } elseif ($status === 'rejected') {
        $rejected[] = $leave;
    } elseif ($status === 'cancelled') {
        $cancelled[] = $leave;
    }
}

// Map sections for the view
$sections = [
    'ongoing' => ['title' => 'Ongoing', 'items' => $ongoing, 'label' => 'Ongoing'],
    'pending' => ['title' => 'Pending Requests', 'items' => $pending, 'label' => 'Pending'],
    'approved' => ['title' => 'Approved History', 'items' => $approved, 'label' => 'Approved'],
    'rejected' => ['title' => 'Rejected Requests', 'items' => $rejected, 'label' => 'Rejected'],
    'cancelled' => ['title' => 'Cancelled', 'items' => $cancelled, 'label' => 'Cancelled']
];

if (!isset($sections[$active_tab])) {
    $active_tab = 'pending';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../../../assets/admin/shared/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .leave-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .leave-emp-info h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .leave-details {
            display: flex;
            gap: 30px;
            color: #666;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .status-cancelled {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-approve {
            background: #10b981;
            color: white;
        }

        .btn-approve:hover {
            background: #059669;
        }

        .btn-reject {
            background: #f43f5e;
            color: white;
        }

        .btn-reject:hover {
            background: #e11d48;
        }

        .reason-box {
            margin-top: 10px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
            font-style: italic;
            font-size: 0.85rem;
            color: #4b5563;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .section-count {
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .ongoing-section h2 { color: #2563eb; }
        .pending-section h2 { color: #d97706; }
        .approved-section h2 { color: #059669; }
        .rejected-section h2 { color: #dc2626; }
        .cancelled-section h2 { color: #64748b; }

        /* Multi-tab UI */
        .tab-nav {
            display: flex;
            gap: 8px;
            margin: 25px 0;
            border-bottom: 1px solid #e2e8f0;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .tab-link {
            padding: 10px 20px;
            text-decoration: none;
            color: #64748b;
            font-weight: 700;
            font-size: 0.9rem;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-link:hover {
            color: var(--accent-color);
        }

        .tab-link.active {
            color: var(--accent-color);
            border-bottom-color: var(--accent-color);
        }

        .tab-count {
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
        }

        .tab-link.active .tab-count {
            background: #dbeafe;
            color: #2563eb;
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 15px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-input {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
        }

        .filter-input:focus {
            border-color: var(--accent-color);
        }
    </style>
</head>

<body>
    <?php
    $activePage = 'leaves';
    $baseUrl = '../';
include __DIR__ . '/../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Leave Management</h1>
                <p style="color: #737373;">Review and manage employee leave requests</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form action="leaves.php" method="GET" class="filter-bar">
            <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
            <div class="filter-group" style="flex: 1; min-width: 200px;">
                <i class="fas fa-search" style="color: #94a3b8;"></i>
                <input type="text" name="search" class="filter-input" placeholder="Search employee..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%;">
            </div>
            <div class="filter-group">
                <select name="month" class="filter-input">
                    <option value="all">All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="year" class="filter-input">
                    <option value="all">All Years</option>
                    <?php 
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 2; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn-action" style="background: var(--accent-color); color: white;">Filter</button>
            <?php if (!empty($search) || !empty($month) || !empty($year)): ?>
                <a href="leaves.php?tab=<?php echo $active_tab; ?>" class="btn-action" style="background: #f1f5f9; color: #64748b; text-decoration: none;">Clear</a>
            <?php endif; ?>
        </form>

        <div class="tab-nav">
            <?php foreach ($sections as $key => $section): ?>
                <a href="leaves.php?tab=<?php echo $key; ?>&search=<?php echo urlencode($search); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                   class="tab-link <?php echo $active_tab === $key ? 'active' : ''; ?>">
                    <?php echo $section['label']; ?>
                    <span class="tab-count"><?php echo count($section['items']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 10px;">
            <?php 
            $current_section = $sections[$active_tab];
            if (empty($current_section['items'])): ?>
                <div class="admin-card" style="text-align: center; padding: 60px;">
                    <i class="fas fa-calendar-check" style="font-size: 3rem; opacity: 0.2; margin-bottom: 15px;"></i>
                    <p style="color: #666;">No <?php echo strtolower($current_section['label']); ?> leaves found for this filter.</p>
                </div>
            <?php else: ?>
                <?php foreach ($current_section['items'] as $leave): ?>
                    <div class="leave-card">
                        <div style="width: 50px; height: 50px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent-color); font-weight: 800; font-size: 1.2rem;">
                            <?php echo strtoupper(substr($leave['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="leave-emp-info">
                                <h4><?php echo htmlspecialchars($leave['full_name']); ?>
                                    <span style="font-weight: 400; font-size: 0.85rem; color: #94a3b8;">(@<?php echo htmlspecialchars($leave['username']); ?>)</span>
                                </h4>
                            </div>
                            <div class="leave-details">
                                <span><i class="fas fa-tag"></i> <?php echo $leave['leave_type']; ?></span>
                                <span><i class="fas fa-calendar-alt"></i>
                                    <?php if ($leave['day_session'] !== 'Full Day'): ?>
                                        <?php echo date('d M Y', strtotime($leave['start_date'])); ?>
                                        <b style="color: #2563eb;">(<?php echo $leave['day_session']; ?>)</b>
                                    <?php else: ?>
                                        <?php echo date('d M Y', strtotime($leave['start_date'])); ?> - <?php echo date('d M Y', strtotime($leave['end_date'])); ?>
                                    <?php endif; ?>
                                </span>
                                <span><i class="fas fa-clock"></i>
                                    <?php echo number_format($leave['total_days'], 1); ?> day(s)
                                </span>
                            </div>
                            <?php if ($leave['reason']): ?>
                                <div class="reason-box">"<?php echo htmlspecialchars($leave['reason']); ?>"</div>
                            <?php endif; ?>
                            <?php if ($leave['document_path']): ?>
                                <div style="margin-top: 10px;">
                                    <a href="../../user/attendance/<?php echo htmlspecialchars($leave['document_path']); ?>" target="_blank" style="font-size: 0.85rem; color: #2563eb; text-decoration: none; font-weight: 600;">
                                        <i class="fas fa-paperclip"></i> View Attachment
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <div style="margin-bottom: 10px;">
                                <span class="status-badge status-<?php echo $leave['status'] ?: 'cancelled'; ?>">
                                    <?php echo $leave['status'] ?: 'cancelled'; ?>
                                </span>
                            </div>
                            <?php if (($leave['status'] === 'pending' || $leave['status'] === '')): ?>
                                <div class="action-btns">
                                    <form action="leaves.php" method="POST" onsubmit="return confirm('Approve this leave request?');">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn-action btn-approve">Approve</button>
                                    </form>
                                    <form action="leaves.php" method="POST" onsubmit="return confirm('Reject this leave request?');">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn-action btn-reject">Reject</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
