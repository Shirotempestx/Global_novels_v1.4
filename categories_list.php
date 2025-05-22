.<?php
session_start();
require_once 'connexion.php';
require_once 'header.php';

// Fetch all categories
$catStmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch novels for each category (limit 10 per category)
$cat_novels = [];
foreach ($categories as $cat) {
    $stmt = $conn->prepare("
        SELECT n.*, u.username as author_name
        FROM novels n
        JOIN novel_categories nc ON n.id = nc.novel_id
        JOIN users u ON n.author_id = u.id
        WHERE nc.category_id = ?
        ORDER BY n.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$cat['id']]);
    $cat_novels[$cat['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Categories - Global Novels</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="index.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    .category-section {
      margin-bottom: 3rem;
    }
    .category-title-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
    }
    .category-title {
      font-size: 1.5rem;
      font-weight: bold;
      color: #222;
    }
    .novel-scroll {
      display: flex;
      overflow-x: auto;
      gap: 1.5rem;
      padding-bottom: 0.5rem;
      scroll-snap-type: x mandatory;
    }
    .novel-scroll::-webkit-scrollbar {
      height: 10px;
    }
    .novel-scroll::-webkit-scrollbar-thumb {
      background: #eee;
      border-radius: 5px;
    }
    .novel-card {
      min-width: 220px;
      max-width: 220px;
      flex: 0 0 220px;
      scroll-snap-align: start;
      height: 420px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .novel-card .card-img-top {
      height: 180px;
      object-fit: cover;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
    }
    .novel-card .card-body {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
    }
    .novel-card .card-footer {
      background: #fff;
      border-top: none;
    }
    .arrow-more-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2.1rem;
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 50%;
      border: 2px solid #ff7e5f;
      color: #ff7e5f;
      background: #fff;
      transition: background 0.2s, color 0.2s, border 0.2s;
      text-decoration: none;
      margin-left: 0.5rem;
    }
    .arrow-more-btn:hover, .arrow-more-btn:focus {
      background: #ff7e5f;
      color: #fff;
      border-color: #feb47b;
      text-decoration: none;
    }
    @media (max-width: 600px) {
      .novel-card { min-width: 160px; max-width: 160px; height: 340px; }
      .category-title { font-size: 1.1rem; }
      .arrow-more-btn { font-size: 1.5rem; width: 2rem; height: 2rem; }
    }
  </style>
</head>
<body>
<div class="container" style="margin-top: 40px;">
  <h1 class="mb-4 text-gradient">Categories</h1>
  <?php foreach ($categories as $cat): ?>
    <div class="category-section">
      <div class="category-title-row">
        <span class="category-title"><?= htmlspecialchars($cat['name']) ?></span>
        <a href="categorie.php?slug=<?= urlencode($cat['slug']) ?>" class="arrow-more-btn" title="See more novels in this category"><i class="bi bi-chevron-right"></i></a>
      </div>
      <?php if (!empty($cat['description'])): ?>
        <div class="mb-2 text-muted" style="max-width:600px;"> <?= htmlspecialchars($cat['description']) ?> </div>
      <?php endif; ?>
      <?php if (empty($cat_novels[$cat['id']])): ?>
        <div class="alert alert-info">No novels in this category yet.</div>
      <?php else: ?>
        <div class="novel-scroll">
          <?php foreach ($cat_novels[$cat['id']] as $novel): ?>
            <div class="card novel-card h-100">
              <img src="uploads/covers/<?= htmlspecialchars($novel['cover_image'] ?? 'default.jpg') ?>" class="card-img-top" alt="<?= htmlspecialchars($novel['title'] ?? '') ?>">
              <div class="card-body text-center">
                <h5 class="card-title" style="font-size:1.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= htmlspecialchars($novel['title']) ?>
                </h5>
                <p class="card-text text-muted mb-2" style="font-size:0.95rem;">
                  By <?= htmlspecialchars($novel['author_name']) ?>
                </p>
                <p class="card-text mb-2" style="font-size:0.95rem;min-height:40px;">
                  <?= htmlspecialchars(mb_strimwidth($novel['description'], 0, 60, '...')) ?>
                </p>
                <a href="novel.php?id=<?= $novel['id'] ?>" class="btn btn-gradient btn-sm">Read More</a>
              </div>
              <div class="card-footer text-center text-muted" style="font-size:0.9rem;">
                <small>Status: <?= ucfirst($novel['status']) ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 