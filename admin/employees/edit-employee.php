<?php
include '../shared/auth.php';
require_once '../../user/attendance/db_connect.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: employees.php");
    exit;
}

$message = "";
$error = "";

try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();

    if (!$employee) {
        $_SESSION['error_msg'] = "Employee not found.";
        header("Location: employees.php");
        exit;
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Fetch Departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // Optional password update
    $shift = $_POST['working_shift'] ?? '8-5';
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;

    if (empty($full_name) || empty($username)) {
        $error = "Full Name and Username are required.";
    } else {
        try {
            // Check if username exists (excluding self)
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                $error = "Username already taken.";
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, username = ?, password = ?, working_shift = ?, department_id = ? WHERE id = ?");
                    $stmt->execute([$full_name, $username, $hashed_password, $shift, $department_id, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, username = ?, working_shift = ?, department_id = ? WHERE id = ?");
                    $stmt->execute([$full_name, $username, $shift, $department_id, $id]);
                }

                $_SESSION['success_msg'] = "Employee updated successfully.";
                header("Location: employees.php");
                exit;
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - PHSB Admin</title>
    <link rel="stylesheet" href="../shared/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #525252;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <?php
    $activePage = 'employees';
    $baseUrl = '../';
    include '../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Edit Employee</h1>
                <p style="color: #737373;">Modify details for <?php echo htmlspecialchars($employee['full_name']); ?></p>
            </div>
            <a href="employees.php" class="btn-primary" style="background: #737373;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <div class="admin-card">
                <form action="edit-employee.php?id=<?php echo $id; ?>" method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required value="<?php echo htmlspecialchars($employee['full_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required value="<?php echo htmlspecialchars($employee['username']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Reset Password (Leave blank to keep current)</label>
                        <input type="password" name="password" placeholder="Enter new password if changing">
                    </div>
                    <div class="form-group">
                        <label>Working Shift</label>
                        <select name="working_shift">
                            <option value="8-5" <?php echo ($employee['working_shift'] == '8-5') ? 'selected' : ''; ?>>8:00 AM - 5:00 PM</option>
                            <option value="830-530" <?php echo ($employee['working_shift'] == '830-530') ? 'selected' : ''; ?>>8:30 AM - 5:30 PM</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id">
                            <option value="">No Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($employee['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px; padding: 14px;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>