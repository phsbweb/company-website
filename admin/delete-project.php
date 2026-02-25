<?php
include 'auth.php';
include '../user/profile_page/includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch image path to delete file
    $stmt = $pdo->prepare("SELECT image_path FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    if ($project && $project['image_path'] && file_exists('../' . $project['image_path'])) {
        unlink('../' . $project['image_path']);
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
