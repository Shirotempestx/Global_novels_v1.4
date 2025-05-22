<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();}
$sql = "
SELECT c.id as comment_id, c.content, c.created_at, c.novel_id, n.title as novel_title, u.username, u.avatar, COUNT(r.id) as reports_count
FROM comment_reports r
JOIN comments c ON r.comment_id = c.id
JOIN novels n ON c.novel_id = n.id
JOIN users u ON c.user_id = u.id
GROUP BY c.id
ORDER BY reports_count DESC, c.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
$profile_sql = "
SELECT u.id as profile_id, u.username, u.avatar, u.role, COUNT(pr.id) as reports_count
FROM profile_reports pr
JOIN users u ON pr.profile_id = u.id
GROUP BY u.id
ORDER BY reports_count DESC
";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->execute();
$profile_reports = $profile_stmt->fetchAll(PDO::FETCH_ASSOC);
$review_sql = "
SELECT r.id as review_id, r.comment, r.rating, r.created_at, r.novel_id, n.title as novel_title, u.username, u.avatar, COUNT(rr.id) as reports_count
FROM review_reports rr
JOIN ratings r ON rr.review_id = r.id
JOIN novels n ON r.novel_id = n.id
JOIN users u ON r.user_id = u.id
GROUP BY r.id
ORDER BY reports_count DESC, r.created_at DESC
";
$review_stmt = $conn->prepare($review_sql);
$review_stmt->execute();
$review_reports = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
$novel_sql = "
SELECT n.id as novel_id, n.title, n.cover_image, u.username, u.id as reporter_id, COUNT(nr.id) as reports_count, GROUP_CONCAT(DISTINCT nr.reason SEPARATOR '; ') as reasons
FROM novel_reports nr
JOIN novels n ON nr.novel_id = n.id
JOIN users u ON nr.user_id = u.id
GROUP BY n.id
ORDER BY reports_count DESC
";
$novel_stmt = $conn->prepare($novel_sql);
$novel_stmt->execute();
$novel_reports = $novel_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>Reported Comments - Admin</title>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <link href='css/bootstrap.min.css' rel='stylesheet'>
  <style>
    body { background: #f5f6fa; min-height: 100vh; padding-top: 110px; }
    .report-card { background: #fff; border-radius: 1rem; box-shadow: 0 2px 16px rgba(0,0,0,0.07); border: 1px solid #ececec; margin-bottom: 2rem; padding: 1rem 1.5rem; }
    .avatar { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
  </style>
</head>
<body style="margin-top:50px">
  <?php include "header.php" ?>
<div class='container'>
  <h2 class='mb-4 text-center'> Reports </h2>
  <ul class="nav nav-tabs mb-4" id="raportsTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">Reported Comments</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">Reported Reviews</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="profiles-tab" data-bs-toggle="tab" data-bs-target="#profiles" type="button" role="tab">Reported Profiles</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="novels-tab" data-bs-toggle="tab" data-bs-target="#novels" type="button" role="tab">Reported Novels</button>
    </li>
  </ul>
  <div class="tab-content"  id="raportsTabsContent">
    <div class="tab-pane fade show active" id="comments" role="tabpanel">
      <?php if (empty($reports)): ?>
        <div class='alert alert-info '>No reported comments.</div>
      <?php else: ?>
        <?php foreach ($reports as $r): ?>
          <div class='report-card d-flex align-items-start'>
            <img src='uploads/avatars/<?= htmlspecialchars($r['avatar'] ?? 'default-avatar.jpg') ?>' class='avatar me-2' alt='avatar'>
            <div class='flex-grow-1'>
              <div><strong><?= htmlspecialchars($r['username']) ?></strong> on <a href='novel.php?id=<?= $r['novel_id'] ?>#comments'><?= htmlspecialchars($r['novel_title']) ?></a></div>
              <div class='text-muted small mb-1'><?= htmlspecialchars($r['created_at']) ?> | <?= $r['reports_count'] ?> report(s)</div>
              <div><?= nl2br(htmlspecialchars($r['content'])) ?></div>
            </div>
            <form method='post' action='delete_comment.php' class='ms-2'>
              <input type='hidden' name='comment_id' value='<?= $r['comment_id'] ?>'>
              <input type='hidden' name='novel_id' value='<?= $r['novel_id'] ?>'>
              <button type='submit' class='btn btn-sm btn-outline-danger' onclick="return confirm('Delete this comment?')">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="tab-pane fade" id="reviews" role="tabpanel">
      <?php if (empty($review_reports)): ?>
        <div class="alert alert-info">No reported reviews.</div>
      <?php else: ?>
        <?php foreach ($review_reports as $r): ?>
          <div class='report-card d-flex align-items-start'>
            <img src='uploads/avatars/<?= htmlspecialchars($r['avatar'] ?? 'default-avatar.jpg') ?>' class='avatar me-2' alt='avatar'>
            <div class='flex-grow-1'>
              <div>
                <strong><?= htmlspecialchars($r['username']) ?></strong>
                on <a href='novel.php?id=<?= $r['novel_id'] ?>#reviews'><?= htmlspecialchars($r['novel_title']) ?></a>
              </div>
              <div class='text-muted small mb-1'><?= htmlspecialchars($r['created_at']) ?> | <?= $r['reports_count'] ?> report(s)</div>
              <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++) echo $i <= $r['rating'] ? '<span style="color:#ffc107;font-size:1.2rem;">★</span>' : '<span style="color:#ccc;font-size:1.2rem;">☆</span>'; ?>
              </div>
              <div><?= nl2br(htmlspecialchars($r['comment'] ?? 'No comment')) ?></div>
            </div>
            <form method='post' action='delete_review.php' class='ms-2'>
              <input type='hidden' name='review_id' value='<?= $r['review_id'] ?>'>
              <input type='hidden' name='novel_id' value='<?= $r['novel_id'] ?>'>
              <button type='submit' class='btn btn-sm btn-outline-danger' onclick="return confirm('Delete this review?')">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="tab-pane fade" id="profiles" role="tabpanel">
      <?php if (empty($profile_reports)): ?>
        <div class="alert alert-info">No reported profiles.</div>
      <?php else: ?>
        <?php foreach ($profile_reports as $p): ?>
          <div class='report-card d-flex align-items-start'>
            <img src='uploads/avatars/<?= htmlspecialchars($p['avatar'] ?? 'default-avatar.jpg') ?>' class='avatar me-2' alt='avatar'>
            <div class='flex-grow-1'>
              <div><strong><?= htmlspecialchars($p['username']) ?></strong> (<?= htmlspecialchars($p['role']) ?>)</div>
              <div class='text-muted small mb-1'><?= $p['reports_count'] ?> report(s)</div>
              <a href='account.php?id=<?= $p['profile_id'] ?>' class='btn btn-sm btn-outline-primary'>View Profile</a>
            </div>
            <form method='post' action='ban_user.php' class='ms-2 d-flex align-items-center'>
              <input type='hidden' name='user_id' value='<?= $p['profile_id'] ?>'>
              <input type='text' name='reason' class='form-control form-control-sm me-2' placeholder='Ban reason (optional)' style='max-width:180px;'>
              <button type='submit' class='btn btn-sm btn-danger' onclick="return confirm('Ban this user?')">Ban User</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="tab-pane fade" id="novels" role="tabpanel">
      <?php if (empty($novel_reports)): ?>
        <div class="alert alert-info">No reported novels.</div>
      <?php else: ?>
        <?php foreach ($novel_reports as $n): ?>
          <div class='report-card d-flex align-items-start'>
            <img src='uploads/covers/<?= htmlspecialchars($n['cover_image'] ?? 'default.jpg') ?>' class='avatar me-2' alt='cover' style='width:60px;height:80px;'>
            <div class='flex-grow-1'>
              <div><strong><?= htmlspecialchars($n['title']) ?></strong></div>
              <div class='text-muted small mb-1'><?= $n['reports_count'] ?> report(s)</div>
              <div class='mb-1'><span class='fw-bold'>Reasons:</span> <?= htmlspecialchars($n['reasons']) ?></div>
              <a href='novel.php?id=<?= $n['novel_id'] ?>' class='btn btn-sm btn-outline-primary'>View Novel</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src='js/bootstrap.bundle.min.js'></script>
<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success text-center">Report submitted successfully.</div>
<?php elseif (isset($_GET['error'])): ?>
  <div class="alert alert-danger text-center">Failed to submit report.</div>
<?php endif; ?>
</body>
</html> 