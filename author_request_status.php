<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reader') {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
// Fetch the user's request
$stmt = $conn->prepare('SELECT * FROM author_requests WHERE user_id = ?');
$stmt->execute([$user_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$request) {
    // If no request exists, redirect to the request page
    header('Location: request_author.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Author Request Status - Global Novels</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Author Request Status</h2>
  <?php if (isset($_GET['success']) && $_GET['success'] === 'submitted'): ?>
    <div class="alert alert-success text-center">Your request has been submitted successfully!</div>
  <?php endif; ?>
  <div class="card mx-auto" style="max-width:600px;">
    <div class="card-header">Request Details</div>
    <ul class="list-group list-group-flush">
      <li class="list-group-item"><strong>Status:</strong> <?= htmlspecialchars(ucfirst($request['status'])) ?></li>
      <li class="list-group-item"><strong>Submitted On:</strong> <?= htmlspecialchars($request['created_at']) ?></li>
      <li class="list-group-item"><strong>Your Reason:</strong> <?= nl2br(htmlspecialchars($request['reason'])) ?></li>
      <li class="list-group-item"><strong>Your Novel Idea:</strong> <?= nl2br(htmlspecialchars($request['novel_idea'])) ?></li>
      <?php if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])): ?>
        <li class="list-group-item"><strong>Rejection Reason:</strong> <?= nl2br(htmlspecialchars($request['rejection_reason'])) ?></li>
      <?php endif; ?>
    </ul>
  </div>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 