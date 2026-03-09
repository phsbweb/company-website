<?php
include '../shared/auth.php';
require_once '../../user/attendance/db_connect.php';

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
        require_once '../shared/logger.php';
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

// Fetch all leave requests with employee details
$query = "SELECT l.*, e.full_name, e.username 
          FROM leaves l 
          JOIN employees e ON l.employee_id = e.id 
          ORDER BY (l.status = 'pending') DESC, l.created_at DESC";
$leaves = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../shared/style.css">
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
    </style>
</head>

<body>
    <?php
    $activePage = 'leaves';
    $baseUrl = '../';
    include '../shared/sidebar.php';
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

        <div style="margin-top: 20px;">
            <?php if (empty($leaves)): ?>
                <div class="admin-card" style="text-align: center; padding: 60px;">
                    <i class="fas fa-calendar-check" style="font-size: 3rem; opacity: 0.2; margin-bottom: 15px;"></i>
                    <p style="color: #666;">No leave requests found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($leaves as $leave): ?>
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
                        </div>
                        <div style="text-align: right;">
                            <div style="margin-bottom: 10px;">
                                <span class="status-badge status-<?php echo $leave['status']; ?>">
                                    <?php echo $leave['status']; ?>
                                </span>
                            </div>
                            <?php if ($leave['status'] === 'pending'): ?>
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