<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $conn->prepare("SELECT n.*, u.username as author FROM novels n JOIN users u ON n.author_id = u.id WHERE n.title LIKE ? OR u.username LIKE ? ORDER BY n.created_at DESC");
    $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
} else {
    $stmt = $conn->prepare("SELECT n.*, u.username as author FROM novels n JOIN users u ON n.author_id = u.id ORDER BY n.created_at DESC");
    $stmt->execute();
}
$novels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Works - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body { background: #f5f6fa; min-height: 100vh; padding-top: 110px; }
    .admin-card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 2px 16px rgba(0,0,0,0.07);
      border: 1px solid #ececec;
      margin-bottom: 2rem;
      padding: 2rem 1.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 220px;
      transition: box-shadow 0.2s, transform 0.2s;
      cursor: pointer;
    }
    .admin-card:hover {
      box-shadow: 0 6px 24px rgba(0,0,0,0.13);
      transform: translateY(-4px) scale(1.03);
    }
    .admin-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      color: #0b5ed7;
    }
    .admin-title {
      font-size: 1.3rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }
    .admin-desc {
      color: #555;
      font-size: 1rem;
      text-align: center;
    }
    @media (max-width: 900px) {
      .row { flex-direction: column; }
      .admin-card { margin-bottom: 1.5rem; }
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Manage Works</h2>
  <form class="mb-3 d-flex" method="get">
    <input type="text" name="search" class="form-control me-2" placeholder="Search by title or author..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
  <?php if (empty($novels)): ?>
    <div class="alert alert-info">No novels found.</div>
  <?php else: ?>
    <table class="table table-bordered table-striped">
      <thead><tr><th>Title</th><th>Author</th><th>Status</th><th>Created At</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($novels as $n): ?>
        <tr>
          <td><?= htmlspecialchars($n['title']) ?></td>
          <td><?= htmlspecialchars($n['author']) ?></td>
          <td><?= htmlspecialchars($n['status']) ?></td>
          <td><?= htmlspecialchars($n['created_at']) ?></td>
          <td>
            <a href="novel.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-info">View</a>
            <button class="btn btn-sm btn-secondary toggle-chapters" data-novel-id="<?= $n['id'] ?>">
              Chapters <span class="arrow">▼</span>
            </button>
            <form method="post" action="delete_novel.php" style="display:inline;">
              <input type="hidden" name="novel_id" value="<?= $n['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this novel and all its chapters?')">Delete</button>
            </form>
            <div class="chapters-container mt-2" id="chapters-<?= $n['id'] ?>" style="display:none;"></div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.toggle-chapters').forEach(button => {
  button.addEventListener('click', function() {
    const novelId = this.dataset.novelId;
    const chaptersContainer = document.getElementById('chapters-' + novelId);
    const arrowSpan = this.querySelector('.arrow');
    if (chaptersContainer.style.display === 'none') {
      // Show chapters
      fetch('get_chapters.php?novel_id=' + novelId)
        .then(res => res.text())
        .then(html => {
          chaptersContainer.innerHTML = html;
          chaptersContainer.style.display = 'block';
          arrowSpan.textContent = '▲'; // Change arrow up
        });
    } else {
      // Hide chapters
      chaptersContainer.style.display = 'none';
      arrowSpan.textContent = '▼'; // Change arrow down
    }
  });
});
</script>
</body>
</html>
