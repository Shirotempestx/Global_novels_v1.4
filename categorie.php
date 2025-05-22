<?php
session_start();
require_once 'connexion.php';
require_once 'header.php';

// Get category slug from URL
$category_slug = $_GET['slug'] ?? '';

if (empty($category_slug)) {
    header('Location: index.php');
    exit();
}

// Get category details
$stmt = $conn->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->execute([$category_slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: index.php');
    exit();
}

// Get novels in this category
$stmt = $conn->prepare("
    SELECT n.*, u.username as author_name 
    FROM novels n 
    JOIN novel_categories nc ON n.id = nc.novel_id 
    JOIN categories c ON nc.category_id = c.id 
    JOIN users u ON n.author_id = u.id 
    WHERE c.slug = ? 
    ORDER BY n.created_at DESC
");
$stmt->execute([$category_slug]);
$novels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($category['name']) ?> - Global Novels</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="index.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

  <div class="container mt-5">
    <h1 class="mb-4"><?= htmlspecialchars($category['name']) ?></h1>
    <?php if ($category['description']): ?>
      <p class="lead mb-4"><?= htmlspecialchars($category['description']) ?></p>
    <?php endif; ?>

    <?php if (empty($novels)): ?>
      <div class="alert alert-info">No novels found in this category yet.</div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($novels as $novel): ?>
          <div class="col-md-4">
            <div class="card novel-card h-100">
              <img src="uploads/covers/<?= htmlspecialchars($novel['cover_image'] ?? 'default.jpg') ?>"
                   class="card-img-top" alt="<?= htmlspecialchars($novel['title']) ?>">
              <div class="card-body text-center">
                <h5 class="card-title"><?= htmlspecialchars($novel['title']) ?></h5>
                <p class="card-text text-muted mb-2">
                  By <?= htmlspecialchars($novel['author_name']) ?>
                </p>
                <p class="card-text mb-3">
                  <?= htmlspecialchars(mb_strimwidth($novel['description'], 0, 100, '...')) ?>
                </p>
                <a href="novel.php?id=<?= $novel['id'] ?>" class="btn btn-gradient">Read More</a>
              </div>
              <div class="card-footer text-center text-muted">
                <small>Status: <?= ucfirst($novel['status']) ?></small>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
