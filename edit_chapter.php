<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header("Location: login.php");
    exit();
}

$author_id = $_SESSION['user_id'];
$chapter_id = $_GET['id'] ?? null;

if (!$chapter_id) {
    header("Location: author_dashboard.php");
    exit();
}

// Fetch chapter details and verify ownership
$stmt = $conn->prepare(
    "SELECT c.*, n.title AS novel_title FROM chapters c
     JOIN novels n ON c.novel_id = n.id
     WHERE c.id = ? AND n.author_id = ?"
);
$stmt->execute([$chapter_id, $author_id]);
$chapter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chapter) {
    echo "You do not have access to this chapter.";
    exit();
}

// Update chapter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = $_POST['title'];
    $number  = $_POST['chapter_number'];
    $content = $_POST['content'];

    $stmt = $conn->prepare(
        "UPDATE chapters SET title = ?, chapter_number = ?, content = ? WHERE id = ?"
    );
    $stmt->execute([$title, $number, $content, $chapter_id]);

    $success = "Chapter updated successfully!";
    // Refresh displayed data
    $chapter['title']          = $title;
    $chapter['chapter_number'] = $number;
    $chapter['content']        = $content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Chapter - <?= htmlspecialchars($chapter['novel_title']) ?></title>
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <!-- CKEditor 4 Standard Build -->
    <script src="https://cdn.ckeditor.com/4.20.2/standard/ckeditor.js"></script>
</head>
<body class="container mt-5">
    <h2>Edit Chapter - <?= htmlspecialchars($chapter['novel_title']) ?></h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Chapter Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($chapter['title']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Chapter Number</label>
            <input type="number" name="chapter_number" class="form-control" value="<?= $chapter['chapter_number'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea id="content" name="content"><?= htmlspecialchars($chapter['content']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="manage_chapters.php?novel_id=<?= $chapter['novel_id'] ?>" class="btn btn-secondary">Back</a>
    </form>

    <script>
        CKEDITOR.replace('content', {
            height: 300,
            language: 'en',
            toolbar: [
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
                { name: 'paragraph',   items: ['NumberedList', 'BulletedList', 'Blockquote'] },
                { name: 'insert',      items: ['Link', 'Unlink', 'Image'] },
                { name: 'styles',      items: ['Format'] },
                { name: 'tools',       items: ['Maximize'] }
            ],
            removePlugins: 'elementspath',
            resize_enabled: false
        });
    </script>
</body>
</html>