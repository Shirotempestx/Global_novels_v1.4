<?php
session_start();
require_once 'connexion.php';
include "header.php";
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$stmt = $conn->prepare('SELECT * FROM banned_users ORDER BY banned_at DESC');
$stmt->execute();
$banned = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Banned Users - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Banned Users</h2>
  <?php if (empty($banned)): ?>
    <div class="alert alert-info">No banned users.</div>
  <?php else: ?>
    <table class="table table-bordered table-striped">
      <thead><tr><th>Username</th><th>Email</th><th>Reason</th><th>Banned At</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($banned as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['reason']) ?></td>
          <td><?= htmlspecialchars($u['banned_at']) ?></td>
          <td>
            <form method="post" action="unban_user.php" style="display:inline;">
              <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
              <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Unban this user?')">Unban</button>
            </form>
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