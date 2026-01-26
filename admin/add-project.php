<?php
include 'auth.php';
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $client = $_POST['client'] ?? '';
    $contract_sum = !empty($_POST['contract_sum']) ? $_POST['contract_sum'] : null;
    $completion_month = !empty($_POST['completion_month']) ? (int)$_POST['completion_month'] : null;
    $completion_year = !empty($_POST['completion_year']) ? (int)$_POST['completion_year'] : null;
    $status = isset($_POST['status']) && $_POST['status'] !== '' ? (int)$_POST['status'] : null;

    $eot_number = !empty($_POST['eot_number']) ? (int)$_POST['eot_number'] : null;
    $eot_month = !empty($_POST['eot_month']) ? (int)$_POST['eot_month'] : null;
    $eot_year = !empty($_POST['eot_year']) ? (int)$_POST['eot_year'] : null;
    $eot = ($eot_number !== null || $eot_month !== null || $eot_year !== null) ? 1 : 0;

    $vo = isset($_POST['vo']) ? 1 : 0;

    // Handle Image Upload
    $image_path = null;
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/projects/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['project_image']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['project_image']['tmp_name'], $target_file)) {
            $image_path = 'assets/images/projects/' . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO projects (title, client, contract_sum, completion_year, completion_month, status, eot, eot_number, eot_month, eot_year, vo, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $client, $contract_sum, $completion_year, $completion_month, $status, $eot, $eot_number, $eot_month, $eot_year, $vo, $image_path])) {
        header('Location: projects.php?msg=added');
        exit;
    } else {
        $error = "Failed to add project.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Project - Priority Horizon Admin</title>
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

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #525252;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 0.95rem;
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
            padding: 40px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            max-width: 800px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #171717;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: #fafafa;
            outline: none;
            font-size: 0.95rem;
            resize: vertical;
        }

        input:focus {
            border-color: var(--accent-color);
        }

        input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            padding-top: 30px;
        }

        .btn-save {
            background: var(--accent-color);
            color: #ffffff;
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancel {
            text-decoration: none;
            color: #737373;
            font-size: 0.9rem;
            margin-left: 20px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-logo">PHSB Admin</div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="projects.php" class="active"><i class="fas fa-project-diagram"></i> Projects</a></li>
            <li><a href="featured.php"><i class="fas fa-star"></i> Featured</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem;">Add New Project</h1>
                <p style="color: #737373;">Enter the project details below</p>
            </div>
        </div>

        <div class="admin-card">
            <?php if (isset($error)): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fee2e2;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="add-project.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Project Title</label>
                        <textarea name="title" placeholder="e.g., Office Tower Renovation" required rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Project Image</label>
                        <input type="file" name="project_image" accept="image/*">
                        <p style="font-size: 0.75rem; color: #737373; margin-top: 5px;">Optional. Recommended size: 800x600px</p>
                    </div>

                    <div class="form-group">
                        <label>Client Name</label>
                        <input type="text" name="client" placeholder="e.g., Corporate Synergy Bhd" required>
                    </div>

                    <div class="form-group">
                        <label>Contract Sum (RM)</label>
                        <input type="number" step="0.01" name="contract_sum" placeholder="e.g., 25000000.00">
                    </div>

                    <div class="form-group">
                        <label>Completion Month</label>
                        <select name="completion_month">
                            <option value="">Select Month</option>
                            <?php
                            $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
                            foreach ($months as $num => $month) echo "<option value='$num'>$month</option>";
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Completion Year</label>
                        <select name="completion_year">
                            <option value="">Select Year</option>
                            <?php
                            $currentYear = date('Y');
                            for ($y = 2012; $y <= $currentYear + 20; $y++) {
                                echo "<option value='$y'>$y</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Select Status</option>
                            <option value="0">Ongoing</option>
                            <option value="1">Completed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="vo" id="vo">
                            <label for="vo" style="margin-bottom: 0;">Variation Order (VO)?</label>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 10px 0;">
                        <h3 style="font-size: 1rem; margin: 20px 0 10px;">Extension of Time (EOT) - Optional</h3>
                    </div>

                    <div class="form-group">
                        <label>EOT Number</label>
                        <input type="number" name="eot_number" placeholder="e.g., 1">
                    </div>

                    <div class="form-group">
                        <label>EOT Month</label>
                        <select name="eot_month">
                            <option value="">Select Month</option>
                            <?php
                            foreach ($months as $num => $month) echo "<option value='$num'>$month</option>";
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>EOT Year</label>
                        <select name="eot_year">
                            <option value="">Select Year</option>
                            <?php
                            for ($y = 2012; $y <= $currentYear + 20; $y++) {
                                echo "<option value='$y'>$y</option>";
                            }
                            ?>
                        </select>
                    </div>

                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-save">Save Project</button>
                    <a href="projects.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>