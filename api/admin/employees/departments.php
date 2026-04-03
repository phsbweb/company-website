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
                if (empty($name)) throw new Exception("Name is required.");
                $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['success_msg'] = "Department added successfully.";
            } elseif ($_POST['action'] === 'delete') {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success_msg'] = "Department deleted successfully.";
            }
            header("Location: departments.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['error_msg'] = "Action failed: " . $e->getMessage();
            header("Location: departments.php");
            exit;
        }
    }
}

// Fetch Departments
$departments = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM employees WHERE department_id = d.id) as emp_count FROM departments d ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../shared/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .dept-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .dept-card:hover {
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .dept-info h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .dept-info p {
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
    $activePage = 'departments';
    $baseUrl = '../';
    include '../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Departments</h1>
                <p style="color: #737373;">Organize employees into logical business units</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-card" style="padding: 20px; margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px;">Add New Department</h3>
            <form action="departments.php" method="POST" style="display: flex; gap: 10px;">
                <input type="hidden" name="action" value="add">
                <input type="text" name="name" placeholder="e.g. Engineering" required style="flex-grow: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-plus"></i> Add
                </button>
            </form>
        </div>

        <div class="dept-grid">
            <?php foreach ($departments as $dept): ?>
                <div class="dept-card-wrapper" style="position: relative;">
                    <a href="employees.php?department_id=<?php echo $dept['id']; ?>" class="dept-card" style="padding-right: 60px;">
                        <div class="dept-info">
                            <h4><?php echo htmlspecialchars($dept['name']); ?></h4>
                            <p><?php echo $dept['emp_count']; ?> Employees Assigned</p>
                        </div>
                        <div style="color: var(--accent-color); font-size: 0.85rem; font-weight: 600;">
                            View List <i class="fas fa-arrow-right" style="margin-left: 5px; font-size: 0.75rem;"></i>
                        </div>
                    </a>

                    <div style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%);">
                        <?php if ($dept['emp_count'] == 0): ?>
                            <form action="departments.php" method="POST" onsubmit="return confirm('Delete this department?');" style="margin: 0;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                                <button type="submit" class="delete-btn" title="Delete Department">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <span title="Cannot delete department with employees" style="opacity: 0.3; cursor: not-allowed; color: #737373; padding: 8px;">
                                <i class="fas fa-trash-alt"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>