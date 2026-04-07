<?php
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../../user/profile_page/includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch image path to delete file
    $stmt = $pdo->prepare("SELECT image_path FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    $image_file = __DIR__ . '/../../user/profile_page/' . ($project['image_path'] ?? '');
    if ($project && $project['image_path'] && file_exists($image_file)) {
        unlink($image_file);
    }

    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    if ($stmt->execute([$id])) {
        // Also remove from featured if it was there
        $stmt = $pdo->prepare("DELETE FROM featured_projects WHERE project_id = ?");
        $stmt->execute([$id]);

        header('Location: projects.php?msg=deleted');
    } else {
        header('Location: projects.php?msg=error');
    }
} else {
    header('Location: projects.php');
}
exit;
