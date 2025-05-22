<?php
require_once 'connexion.php';
$chapter_id = $_GET['id'] ?? null;

if (!$chapter_id) {
    echo "Invalid request.";
    exit();
}

$stmt = $conn->prepare("SELECT c.title, c.content, n.title AS novel_title FROM chapters c JOIN novels n ON c.novel_id = n.id WHERE c.id = ?");
$stmt->execute([$chapter_id]);
$chapter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chapter) {
    echo "Chapter not found.";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Preview - <?php echo htmlspecialchars($chapter['title']); ?></title>
    <link href="css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="container mt-4">
    <h3><?php echo htmlspecialchars($chapter['novel_title']); ?> - <?php echo htmlspecialchars($chapter['title']); ?></h3>
    <hr>
    <div class="mt-3">
        <?php echo nl2br($chapter['content']); ?>
    </div>
</body>
</html>
