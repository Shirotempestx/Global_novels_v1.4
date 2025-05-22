<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reader') {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
// Check if user already has a pending or accepted request
$stmt = $conn->prepare('SELECT status FROM author_requests WHERE user_id = ?');
$stmt->execute([$user_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
if ($request && ($request['status'] === 'pending' || $request['status'] === 'accepted')) {
    header('Location: author_request_status.php');
    exit();
}
// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    $novel_idea = trim($_POST['novel_idea'] ?? '');
    if (!empty($reason) && !empty($novel_idea)) {
        $stmt = $conn->prepare('INSERT INTO author_requests (user_id, reason, novel_idea) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $reason, $novel_idea]);
        header('Location: author_request_status.php?success=submitted');
        exit();
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Request Author Account - Global Novels</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Request Author Account</h2>
  <?php if (isset($error)): ?>
    <div class="alert alert-danger text-center"><?= $error ?></div>
  <?php endif; ?>
  <form method="post" class="mx-auto" style="max-width:600px;">
    <p>If you wish to become an author on Global Novels, please fill out the form below.</p>
    <div class="mb-3">
      <label class="form-label">Why do you want to become an author?</label>
      <textarea name="reason" class="form-control" rows="4" required></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">What is the idea for your first novel?</label>
      <textarea name="novel_idea" class="form-control" rows="4" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Submit Request</button>
  </form>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 