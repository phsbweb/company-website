<?php
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../../user/profile_page/includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: projects.php');
    exit;
}

// Fetch current project data
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $client = $_POST['client'] ?? '';


    // Handle Image Upload
    $image_path = $project['image_path'];
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../user/profile_page/assets/images/projects/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['project_image']['name']);
        $target_file = $upload_dir . '/' . $file_name;

        if (move_uploaded_file($_FILES['project_image']['tmp_name'], $target_file)) {
            // Delete old image if it exists
            $old_image_file = __DIR__ . '/../../user/profile_page/' . $image_path;
            if ($image_path && file_exists($old_image_file)) {
                unlink($old_image_file);
            }
            $image_path = 'assets/images/projects/' . $file_name;
        }
    }

    $stmt = $pdo->prepare("UPDATE projects SET title = ?, client = ?, image_path = ? WHERE id = ?");
    if ($stmt->execute([$title, $client, $image_path, $id])) {
        header('Location: projects.php?msg=updated');
        exit;
    } else {
        $error = "Failed to update project.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Priority Horizon Admin</title>
    <link rel="stylesheet" href="../../../assets/admin/shared/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

        .btn-cancel {
            text-decoration: none;
            color: #737373;
            font-size: 0.9rem;
            margin-left: 20px;
        }
    </style>
</head>

<body>

    <?php
    $activePage = 'projects';
    $baseUrl = '../';
    include __DIR__ . '/../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem;">Edit Project</h1>
                <p style="color: #737373;">Modify the existing project information</p>
            </div>
        </div>

        <div class="admin-card">
            <?php if (isset($error)): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fee2e2;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="edit-project.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Project Title</label>
                        <textarea name="title" required rows="2"><?php echo htmlspecialchars($project['title']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Project Image</label>
                        <?php if ($project['image_path']): ?>
                            <div style="margin-bottom: 15px;">
                                <img src="../../user/profile_page/<?php echo htmlspecialchars($project['image_path']); ?>" alt="Current Project Image" style="width: 150px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <p style="font-size: 0.75rem; color: #737373;">Current image. Uploading a new one will replace it.</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="project_image" accept="image/*">
                        <p style="font-size: 0.75rem; color: #737373; margin-top: 5px;">Optional. Recommended size: 800x600px</p>
                    </div>

                    <div class="form-group">
                        <label>Client Name</label>
                        <input type="text" name="client" value="<?php echo htmlspecialchars($project['client']); ?>" required>
                    </div>


                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-save">Update Project</button>
                    <a href="projects.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>
