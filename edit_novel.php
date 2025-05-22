<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header("Location: login.php");
    exit();
}

$author_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid novel ID.";
    exit();
}

$novel_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM novels WHERE id = ? AND author_id = ?");
$stmt->execute([$novel_id, $author_id]);
$novel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$novel) {
    echo "Novel not found or you don't have permission to edit it.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $cover_image = $novel['cover_image'];

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "uploads/covers/";
        $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $new_image = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_image;

        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
            $cover_image = $new_image;
        }
    }

    $update_stmt = $conn->prepare("UPDATE novels SET title = ?, description = ?, type = ?, status = ?, cover_image = ?, updated_at = NOW() WHERE id = ? AND author_id = ?");
    $update_stmt->execute([$title, $description, $type, $status, $cover_image, $novel_id, $author_id]);

    $success = "Novel updated successfully!";
    $stmt->execute([$novel_id, $author_id]);
    $novel = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Novel</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Novel</h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($novel['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($novel['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Cover</label><br>
            <img src="uploads/covers/<?= htmlspecialchars($novel['cover_image']) ?>" alt="Cover Image" style="max-width: 200px;">
        </div>

        <div class="mb-3">
            <label class="form-label">New Cover Image (optional)</label>
            <input type="file" name="cover_image" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label class="form-label">type</label>
            <select name="type" class="form-select" required>
                <option value="original" <?= $novel['type'] === 'original' ? 'selected' : '' ?>>Original</option>
                <option value="translated" <?= $novel['type'] === 'translated' ? 'selected' : '' ?>>Translated</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="ongoing" <?= $novel['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                <option value="completed" <?= $novel['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="hiatus" <?= $novel['status'] === 'hiatus' ? 'selected' : '' ?>>Hiatus</option>
                <option value="dropped" <?= $novel['status'] === 'dropped' ? 'selected' : '' ?>>Dropped</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="author_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
