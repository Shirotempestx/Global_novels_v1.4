<?php
session_start();
require_once 'connexion.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header("Location: login.php");
    exit();
}
$author_id = $_SESSION['user_id'];

$sql = "
SELECT n.*, 
       COALESCE(s.views, 0) AS views, 
       COALESCE(s.average_rating, 0) AS avg_rating, 
       COALESCE(s.ratings_count, 0) AS ratings_count
FROM novels n
LEFT JOIN statistics s ON n.id = s.novel_id
WHERE n.author_id = ?
ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$author_id]);
$novels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Works - Global Novels</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f5f6fa; min-height: 100vh; padding-top: 110px; }
    .work-card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 2px 16px rgba(0,0,0,0.07);
      border: 1px solid #ececec;
      margin-bottom: 2rem;
      padding: 1.5rem 2rem;
      display: flex;
      flex-direction: row;
      align-items: flex-start;
      gap: 2rem;
      width: 100%;
    }
    .work-cover {
      width: 160px;
      height: 220px;
      object-fit: cover;
      border-radius: 0.75rem;
      border: 1px solid #eee;
      background: #fafafa;
    }
    .work-info {
      flex: 1 1 0%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .work-title {
      font-size: 1.5rem;
      font-weight: bold;
      color: #222;
      margin-bottom: 0.5rem;
    }
    .work-desc {
      color: #555;
      margin-bottom: 1rem;
      font-size: 1.05rem;
    }
    .work-meta {
      display: flex;
      gap: 2rem;
      margin-bottom: 1rem;
      flex-wrap: wrap;
    }
    .work-meta span {
      font-size: 1.1rem;
      color: #666;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }
    .work-actions {
      margin-top: 1rem;
      display: flex;
      gap: 1rem;
    }
    @media (max-width: 700px) {
      .work-card { flex-direction: column; align-items: stretch; padding: 1rem; }
      .work-cover { width: 100%; height: 180px; }
    }
  </style>
</head>
<body>
<div class="container">
  <h2 class="mb-4 text-center">My Works</h2>
  <?php if (empty($novels)): ?>
    <div class="alert alert-info">You have not added any novels yet.</div>
  <?php else: ?>
    <?php foreach ($novels as $novel): ?>
      <div class="work-card">
        <img src="uploads/covers/<?= htmlspecialchars($novel['cover_image'] ?? 'default.jpg') ?>" class="work-cover" alt="<?= htmlspecialchars($novel['title']) ?>">
        <div class="work-info">
          <div>
            <div class="work-title"> <?= htmlspecialchars($novel['title']) ?> </div>
            <div class="work-desc"> <?= htmlspecialchars(mb_strimwidth($novel['description'], 0, 200, '...')) ?> </div>
            <div class="work-meta">
              <span title="Views"><i class="bi bi-eye"></i> <?= (int)$novel['views'] ?> views</span>
              <span title="Average Rating"><i class="bi bi-star-fill text-warning"></i> <?= number_format($novel['avg_rating'], 2) ?> / 5</span>
              <span title="Ratings Count"><i class="bi bi-people"></i> <?= (int)$novel['ratings_count'] ?> ratings</span>
              <span title="Status"><i class="bi bi-info-circle"></i> <?= ucfirst($novel['status']) ?></span>
              <span title="Type"><i class="bi bi-book"></i> <?= ucfirst($novel['type']) ?></span>
              <span title="Created"><i class="bi bi-calendar"></i> <?= htmlspecialchars(date('Y-m-d', strtotime($novel['created_at']))) ?></span>
            </div>
          </div>
          <div class="work-actions">
            <a href="novel.php?id=<?= $novel['id'] ?>" class="btn btn-gradient btn-sm">View</a>
            <a href="edit_novel.php?id=<?= $novel['id'] ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
            <a href="delete_novel.php?id=<?= $novel['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this novel and all its chapters?')">Delete</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html> 