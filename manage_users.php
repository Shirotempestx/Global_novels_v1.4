<?php
session_start();
require_once 'connexion.php';
include "header.php";
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
} else {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Manage Users</h2>
  <form class="mb-3 d-flex" method="get">
    <input type="text" name="search" class="form-control me-2" placeholder="Search by username or email..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
  <?php if (empty($users)): ?>
    <div class="alert alert-info">No users found.</div>
  <?php else: ?>
    <table class="table table-bordered table-striped">
      <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Created At</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['role']) ?></td>
          <td><?= htmlspecialchars($u['created_at']) ?></td>
          <td>
            <a href="account.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-info">Profile</a>
            <?php if ($u['role'] !== 'admin'): ?>
              <form method="post" action="ban_user.php" style="display:inline;">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <input type="text" name="reason" class="form-control form-control-sm d-inline-block me-2" placeholder="Ban reason (optional)" style="max-width:140px;display:inline-block;">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Ban this user?')">Ban</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 