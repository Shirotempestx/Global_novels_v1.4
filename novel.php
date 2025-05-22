<?php
require_once 'connexion.php';
include 'header.php';
// Get novel ID from URL
$novel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch novel details
$novel_query = "SELECT n.*, u.username as author, u.id AS author_user_id,
                COALESCE(s.views, 0) as views,
                COALESCE(s.ratings_count, 0) as ratings_count,
                COALESCE(s.average_rating, 0) as average_rating
                FROM novels n
                LEFT JOIN users u ON n.author_id = u.id
                LEFT JOIN statistics s ON n.id = s.novel_id
                WHERE n.id = ?";
$stmt = $conn->prepare($novel_query);
$stmt->execute([$novel_id]);
$novel = $stmt->fetch();

if (!$novel) {
    header("Location: index.php");
    exit();
}

// Fetch categories for this novel
$categories_query = "SELECT c.name 
                    FROM novel_categories nc
                    JOIN categories c ON nc.category_id = c.id
                    WHERE nc.novel_id = ?";
$stmt = $conn->prepare($categories_query);
$stmt->execute([$novel_id]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);



//  fetch chapters for this novel:
$chapters_query = "
    SELECT id, title, chapter_number 
    FROM chapters 
    WHERE novel_id = ? 
    ORDER BY chapter_number ASC
";
$stmt = $conn->prepare($chapters_query);
$stmt->execute([$novel['id']]);
$chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Fetch reviews
$reviews_query = "SELECT r.*, u.username, u.avatar 
                  FROM ratings r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.novel_id = ?
                  ORDER BY r.created_at DESC
                  LIMIT 5";
$stmt = $conn->prepare($reviews_query);
$stmt->execute([$novel_id]);
$reviews = $stmt->fetchAll();

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$is_fav = false;
if ($is_logged_in) {
    $fav_stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND novel_id = ?");
    $fav_stmt->execute([$user_id, $novel_id]);
    $is_fav = $fav_stmt->fetchColumn() ? true : false;
}
$has_rated = false;
if ($is_logged_in) {
    $rate_stmt = $conn->prepare("SELECT 1 FROM ratings WHERE user_id = ? AND novel_id = ?");
    $rate_stmt->execute([$user_id, $novel_id]);
    $has_rated = $rate_stmt->fetchColumn() ? true : false;
}
$comments_stmt = $conn->prepare("SELECT c.*, u.username, u.avatar, u.id as commenter_id, u.role as commenter_role FROM comments c JOIN users u ON c.user_id = u.id WHERE c.novel_id = ? ORDER BY c.created_at DESC");
$comments_stmt->execute([$novel_id]);
$comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($novel['title']); ?> - GLOBAL NOVELS</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            padding-top: 70px;
            background-color: #f8f9fa;
        }
        .transition {
            transition: all 0.3s ease;
            border-width: 2px;
        }
        .transition:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-outline-light:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .btn-primary:hover {
            background-color: #0b5ed7 !important;
            border-color: #0b5ed7 !important;
        }
        .text-gradient {
            background: linear-gradient(90deg, #ff7e5f, #feb47b);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h1.display-4 {
            font-size: 2.5rem;
            letter-spacing: 1.5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        .novel-header {
            
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        .novel-cover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            transition: transform 0.3s ease;
        }
        .novel-cover:hover {
            transform: scale(1.03);
        }
        .chapter-item {
            background-color: #6c757d;
            color: #f8f9fa;
            padding: 15px;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .chapter-item:hover {
            background-color: #5a6268;
            text-decoration: none;
            color: white;
        }
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .genre-badge {
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .cover{
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                              url('uploads/covers/<?php echo htmlspecialchars($novel['cover_image']); ?>');
            background-size: cover;
            background-position: center;

        }
        .author-link {
            font-size: 1.1em; /* Slightly larger */
            color: #007bff !important; /* Blue color */
        }
        /* Reviews Section Styling */
        .review-item {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .review-avatar {
            border: 2px solid #007bff;
            flex-shrink: 0;
        }
        .review-username {
            color: #007bff;
            font-size: 1.1em;
        }
        .review-date {
            font-size: 0.9em;
            color: #777;
        }
        .review-content {
            margin-top: 10px;
            color: #333;
            line-height: 1.6;
        }
        .rating-stars .star-icon {
            color: #ffc107; /* Golden color for stars */
            font-size: 1.2rem;
            margin-right: 2px;
        }
        /* .rating-stars .star-icon.filled {
            No specific style needed, default color is golden
     } */
        .review-actions { /* Style for delete/report buttons container */
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 5px; /* Space between buttons */
        }

        /* Add Review Section Styling */
        .rating-input {
            display: inline-block;
            unicode-bidi: bidi-override;
            direction: rtl;
            text-align: left;
        }
        .rating-input > input {
            display: none;
        }
        .rating-input > label:before {
            content: '\2605'; /* Unicode star */
            font-size: 1.5rem;
            padding: 5px;
            cursor: pointer;
            color: #ccc; /* Default star color */
            transition: color 0.2s ease-in-out;
        }
        .rating-input > input:checked ~ label:before,
        .rating-input > input:checked ~ label:before {
            color: #ffc107; /* Filled star color */
        }
        .rating-input > label:hover:before,
        .rating-input > label:hover ~ label:before {
            color: #ffc107; /* Hover star color */
        }

        /* Comments Section Styling */
        .comment-item {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .comment-avatar {
            border: 1px solid #ccc;
            flex-shrink: 0;
        }
         .comment-username {
            color: #007bff;
            font-weight: bold;
        }
        .comment-date {
            font-size: 0.8em;
            color: #999;
        }
        .comment-content {
            margin-top: 5px;
            color: #444;
            line-height: 1.5;
        }
         .comment-actions { /* Style for delete/report buttons container in comments */
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 5px; /* Space between buttons */
        }
    </style>
</head>
<body class="cover">


    <div class="novel-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <img src="uploads/covers/<?php echo htmlspecialchars($novel['cover_image']); ?>" class="img-fluid novel-cover rounded" alt="<?php echo htmlspecialchars($novel['title']); ?>">
                </div>
                <div class="col-md-9">
                    <div class="d-flex align-items-center mb-2">
                        <h1 class="display-4 mb-0"><?php echo htmlspecialchars($novel['title']); ?></h1>
                        
                    </div>
                    <p class="lead">By <a href="account.php?id=<?php echo $novel['author_user_id'] ?? ''; ?>" class="author-link text-decoration-none text-white"><?php echo htmlspecialchars($novel['author']??"Unknown"); ?></a></p>
                    <div class="mb-3">
                        <?php foreach ($categories as $category): ?>
                            <?php
                                    $cat_slug_stmt = $conn->prepare("SELECT slug FROM categories WHERE name = ? LIMIT 1");
                            $cat_slug_stmt->execute([$category]);
                            $cat_slug = $cat_slug_stmt->fetchColumn();
                            ?>
                            <a href="categorie.php?slug=<?= urlencode($cat_slug) ?>" class="genre-badge text-decoration-none" style="cursor:pointer;">
                                <?= htmlspecialchars($category) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="rating-stars me-2">
                            <?php
                            $full_stars = floor($novel['average_rating']);
                            $half_star = ($novel['average_rating'] - $full_stars) >= 0.25 && ($novel['average_rating'] - $full_stars) < 0.75;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<span style="color:#ffc107;font-size:1.2rem;">★</span>';
                            }
                            if ($half_star) {
                                echo '<span style="color:#ffc107;font-size:1.2rem;">&#189;</span>';
                            }
                            for ($i = 0; $i < $empty_stars; $i++) {
                                echo '<span style="color:#ccc;font-size:1.2rem;">☆</span>';
                            }
                            ?>
                        </div>
                        <span>(<?php echo $novel['ratings_count']; ?> ratings)</span>
                    </div>
                    <p class="mb-3"><strong>Status:</strong> <?php echo ucfirst($novel['status']); ?></p>
                    <p class="mb-3"><strong>Views:</strong> <?php echo number_format($novel['views']); ?></p>
                    <?php if ($is_logged_in): ?>
                            <form method="post" action="toggle_favorite.php" class="ms-3">
                                <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                                <button type="submit" class="btn btn-sm <?= $is_fav ? 'btn-danger' : 'btn-outline-danger' ?> ms-2">
                                    <i class="bi <?= $is_fav ? 'bi-heart-fill' : 'bi-heart' ?>"></i> <?= $is_fav ? 'Remove Favorite' : 'Add to Favorites' ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-danger btn-sm ms-3" onclick="alert('You must be logged in to favorite novels.')">
                                <i class="bi bi-heart"></i> Add to Favorites
                            </button>
                        <?php endif; ?>
                    <?php if (!empty($chapters)): ?>
                        <a href="chapter.php?id=<?php echo $chapters[0]['id']; ?>" class="btn btn-primary btn-lg">
                            Start Reading (Chapter 1)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4 ">
                        <div class="card-body">
                            <h3 class="card-title">Description</h3>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($novel['description'])); ?></p>
                        </div>
                    </div>

<!-- Show Chapters button -->
<div class="container my-4 text-center">
  <button id="toggleChaptersBtn" class="btn btn-outline-primary">
    Show Chapters ▼
  </button>
</div>

<!-- Chapters list (hidden by default) -->
<div id="chaptersContainer" class="container mb-5" style="display: none;">
  <?php if (!empty($chapters)): ?>
    <div class="card">
      <div class="card-body">
        <h3 class="card-title mb-4">
          Chapters (<?= count($chapters) ?>)
        </h3>
        <div class="list-group">
          <?php foreach ($chapters as $ch): ?>
            <a href="chapter.php?id=<?= $ch['id'] ?>"
               class="list-group-item list-group-item-action d-flex justify-content-between">
              <span>
                Chapter <?= $ch['chapter_number'] ?>:
                <?= htmlspecialchars($ch['title']) ?>
              </span>
              <span>→</span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="text-muted text-center">No chapters added yet.</div>
  <?php endif; ?>
</div>

<script>
  const btn       = document.getElementById('toggleChaptersBtn');
  const container = document.getElementById('chaptersContainer');

  btn.addEventListener('click', () => {
    const showing = container.style.display === 'block';
    container.style.display = showing ? 'none' : 'block';
    btn.textContent = showing
      ? 'Show Chapters ▼'
      : 'Hide Chapters ▲';
  });
</script>


                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Novel Stats</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Total Chapters
                                    <span class="badge bg-primary rounded-pill"><?php echo count($chapters); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Total Views
                                    <span class="badge bg-primary rounded-pill"><?php echo number_format($novel['views']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Average Rating
                                    <span class="badge bg-primary rounded-pill"><?php echo number_format($novel['average_rating'], 1); ?>/5</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Status
                                    <span class="badge <?php echo $novel['status'] == 'completed' ? 'bg-success' : 'bg-warning'; ?> rounded-pill">
                                        <?php echo ucfirst($novel['status']); ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <?php if (!empty($reviews)): ?>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Reviews</h5>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-item mb-3 pb-3 border-bottom d-flex align-items-start">
                                        <img src="uploads/avatars/<?= htmlspecialchars($review['avatar'] ?? 'default-avatar.jpg') ?>" alt="avatar" class="rounded-circle me-3 review-avatar" style="width:45px;height:45px;object-fit:cover;">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <a href="account.php?id=<?= $review['user_id'] ?>" class="fw-bold text-decoration-none review-username"><?= htmlspecialchars($review['username']) ?></a>
                                                <span class="text-muted small review-date"><?= htmlspecialchars($review['created_at']) ?></span>
                                            </div>
                                            <div class="rating-stars mb-2">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $review['rating'] ? '<span class="star-icon filled">★</span>' : '<span class="star-icon">☆</span>';
                                                }
                                                ?>
                                            </div>
                                            <div class="review-content"><?= nl2br(htmlspecialchars($review['comment'] ?? 'No comment')) ?></div>
                                        </div>
                                        <div class="review-actions ms-3">
                                            <?php if ($is_logged_in && ($user_id == $review['user_id'] || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))): ?>
                                                <form method="post" action="delete_review.php" style="display:inline-block;">
                                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                    <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this review?')">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($is_logged_in && $user_id == $novel['author_user_id']): ?>
                                                <form method="post" action="report_review.php" style="display:inline-block;">
                                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                    <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                                                    <button type="submit" class="btn btn-outline-warning btn-sm">Report</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h4 class="card-title">Leave a Review</h4>
                    <?php if (!$is_logged_in): ?>
                        <div class="alert alert-warning">You must be logged in to leave a review.</div>
                    <?php elseif ($has_rated): ?>
                        <div class="alert alert-info">You have already rated this novel.</div>
                    <?php else: ?>
                        <form action="submit_review.php" method="POST">
                            <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
                            <div class="mb-3">
                                <label for="rating" class="form-label">Your Rating</label>
                                <div class="rating-input">
                                    <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 stars"></label>
                                    <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars"></label>
                                    <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars"></label>
                                    <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars"></label>
                                    <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star"></label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Your Review (Optional)</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-5">
                <div class="card-body">
                    <h4 class="card-title">Comments</h4>
                    <?php if ($is_logged_in): ?>
                        <form action="submit_comment.php" method="POST" class="mb-4">
                            <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                            <div class="mb-2">
                                <textarea name="content" class="form-control" rows="2" placeholder="Write your comment..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Add Comment</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">You must be logged in to comment.</div>
                    <?php endif; ?>
                    <?php if (empty($comments)): ?>
                        <div class="text-muted">No comments yet.</div>
                    <?php else: ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="comment-item border-bottom py-2 mb-2 d-flex align-items-start">
                                <img src="uploads/avatars/<?= htmlspecialchars($c['avatar'] ?? 'default-avatar.jpg') ?>" alt="avatar" class="rounded-circle me-2 comment-avatar" style="width:40px;height:40px;object-fit:cover;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <a href="account.php?id=<?= $c['commenter_id'] ?>" class="fw-bold text-decoration-none comment-username"><?= htmlspecialchars($c['username']) ?></a>
                                        <span class="text-muted small comment-date"><?= htmlspecialchars($c['created_at']) ?></span>
                                    </div>
                                    <div class="comment-content"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
                                </div>
                                <div class="comment-actions ms-3">
                                    <?php if ($is_logged_in && ($user_id == $c['commenter_id'] || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))): ?>
                                        <form method="post" action="delete_comment.php" style="display:inline-block;">
                                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this comment?')">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($is_logged_in && $user_id != $c['commenter_id'] && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')): ?>
                                        <form method="post" action="report_comment.php" style="display:inline-block;">
                                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Report</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_logged_in && $user_id != $novel['author_user_id'] && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')): ?>
                <form method="post" action="report_novel.php" class="d-inline">
                    <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                    <input type="text" name="reason" class="form-control d-inline w-auto" placeholder="Reason (optional)" style="display:inline-block;max-width:200px;">
                    <button type="submit" class="btn btn-outline-warning btn-sm ms-2">Report Novel</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-dark text-white py-4">
        <div class="container d-flex justify-content-between align-items-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> GLOBAL NOVELS. All rights reserved.</p>
            <nav>
                <ul class="nav">
                    <li class="nav-item"><a href="about.php" class="nav-link text-white">About Us</a></li>
                    <li class="nav-item"><a href="terms.php" class="nav-link text-white">Terms of Service</a></li>
                    <li class="nav-item"><a href="privacy.php" class="nav-link text-white">Privacy Policy</a></li>
                </ul>
            </nav>
        </div>
    </footer>

    <script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success text-center">Report submitted successfully.</div>
<?php elseif (isset($_GET['error'])): ?>
  <div class="alert alert-danger text-center">You have already reported this item.</div>
<?php endif; ?>