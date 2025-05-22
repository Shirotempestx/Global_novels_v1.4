<?php
session_start();
require_once 'connexion.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$sql = "
SELECT n.*
FROM favorites f
JOIN novels n ON f.novel_id = n.id
WHERE f.user_id = ?
ORDER BY f.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>Favorites - Global Novels</title>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <link href='css/bootstrap.min.css' rel='stylesheet'>
  <style>
    body { background: #f5f6fa; min-height: 100vh; padding-top: 110px; }
    .fav-card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 2px 16px rgba(0,0,0,0.07);
      border: 1px solid #ececec;
      margin-bottom: 2rem;
      padding: 1rem 1.5rem;
      display: flex;
      align-items: center;
      gap: 1.5rem;
      width: 100%;
    }
    .fav-cover {
      width: 110px;
      height: 150px;
      object-fit: cover;
      border-radius: 0.75rem;
      border: 1px solid #eee;
      background: #fafafa;
    }
    .fav-info { flex: 1 1 0%; }
    .fav-title {
      font-size: 1.2rem;
      font-weight: bold;
      color: #222;
      margin-bottom: 0.5rem;
    }
    .fav-actions { display: flex; gap: 1rem; margin-top: 0.5rem; }
    @media (max-width: 700px) {
      .fav-card { flex-direction: column; align-items: stretch; padding: 1rem; }
      .fav-cover { width: 100%; height: 120px; }
    }
  </style>
</head>
<body>
<div class='container'>
  <h2 class='mb-4 text-center'>My Favorites</h2>
  <?php if (empty($favorites)): ?>
    <div class='alert alert-info'>You have not added any novels to your favorites yet.</div>
  <?php else: ?>
    <?php foreach ($favorites as $novel): ?>
      <div class='fav-card'>
        <img src='uploads/covers/<?= htmlspecialchars($novel['cover_image'] ?? 'default.jpg') ?>' class='fav-cover' alt='<?= htmlspecialchars($novel['title']) ?>'>
        <div class='fav-info'>
          <div class='fav-title'><?= htmlspecialchars($novel['title']) ?></div>
          <div class='fav-actions'>
            <a href='novel.php?id=<?= $novel['id'] ?>' class='btn btn-gradient btn-sm'>View</a>
            <a href='remove_favorite.php?id=<?= $novel['id'] ?>' class='btn btn-outline-danger btn-sm' onclick="return confirm('Remove from favorites?')">Remove</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script src='js/bootstrap.bundle.min.js'></script>
</body>
</html> 