<?php
include '../shared/auth.php';
require_once '../../user/attendance/db_connect.php';

$message = $_SESSION['success_msg'] ?? "";
$error = $_SESSION['error_msg'] ?? "";
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $name = trim($_POST['name']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'] ?: $start_date;

                if (empty($name) || empty($start_date)) throw new Exception("Name and Start Date are required.");
                if (strtotime($start_date) > strtotime($end_date)) throw new Exception("Start date cannot be after end date.");

                $stmt = $pdo->prepare("INSERT INTO holidays (name, start_date, end_date) VALUES (?, ?, ?)");
                $stmt->execute([$name, $start_date, $end_date]);
                $_SESSION['success_msg'] = "Holiday added successfully.";
            } elseif ($_POST['action'] === 'delete') {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM holidays WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success_msg'] = "Holiday deleted successfully.";
            }
            header("Location: holidays.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['error_msg'] = "Action failed: " . $e->getMessage();
            header("Location: holidays.php");
            exit;
        }
    }
}

// Fetch Holidays
$holidays = $pdo->query("SELECT * FROM holidays ORDER BY start_date ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Holidays - Admin</title>
    <link rel="stylesheet" href="../shared/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .holiday-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .holiday-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .holiday-info h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .holiday-info p {
            font-size: 0.85rem;
            color: #737373;
        }

        .delete-btn {
            color: var(--danger-color);
            background: #fef2f2;
            border: none;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .delete-btn:hover {
            background: #fee2e2;
        }
    </style>
</head>

<body>
    <?php
    $activePage = 'holidays';
    $baseUrl = '../';
    include '../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Public Holidays</h1>
                <p style="color: #737373;">Manage dates to be excluded from leave calculations</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-card" style="padding: 20px; margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px;">Add New Holiday</h3>
            <form action="holidays.php" method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
                <input type="hidden" name="action" value="add">
                <div style="flex-grow: 1;">
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; color: #737373;">Holiday Name</label>
                    <input type="text" name="name" placeholder="e.g. Chinese New Year" required
                        style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; color: #737373;">Start Date</label>
                    <input type="date" name="start_date" required
                        style="padding: 9px; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; color: #737373;">End Date (Optional)</label>
                    <input type="date" name="end_date"
                        style="padding: 9px; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
                <button type="submit" class="btn-primary" style="height: 42px;">
                    <i class="fas fa-plus"></i> Add Holiday
                </button>
            </form>
        </div>

        <div class="holiday-grid">
            <?php if (empty($holidays)): ?>
                <p style="color: #737373; grid-column: 1/-1; text-align: center; padding: 40px; background: #fff; border-radius: 12px; border: 1px dashed var(--border-color);">
                    No holidays added yet.
                </p>
            <?php endif; ?>
            <?php foreach ($holidays as $h): ?>
                <div class="holiday-card">
                    <div class="holiday-info">
                        <h4><?php echo htmlspecialchars($h['name']); ?></h4>
                        <p>
                            <i class="far fa-calendar-alt"></i>
                            <?php
                            if ($h['start_date'] === $h['end_date']) {
                                echo date('d M Y', strtotime($h['start_date']));
                            } else {
                                echo date('d M', strtotime($h['start_date'])) . " - " . date('d M Y', strtotime($h['end_date']));
                            }
                            ?>
                        </p>
                    </div>
                    <form action="holidays.php" method="POST" onsubmit="return confirm('Delete this holiday?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                        <button type="submit" class="delete-btn" title="Delete Holiday">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>