<?php
require_once 'connexion.php'; 

$chapter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


// Fetch chapter details
$chapter_query = "
    SELECT c.*, 
           n.title AS novel_title, 
           n.id AS novel_id, 
           u.username AS author, 
           n.cover_image, 
           u.id AS author_id
    FROM chapters c
    JOIN novels n ON c.novel_id = n.id
    JOIN users u ON n.author_id = u.id
    WHERE c.id = ?
";

$stmt = $conn->prepare($chapter_query);
$stmt->execute([$chapter_id]);
$chapter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chapter) {
    // Chapter not found, display a message
    $chapter_not_found = true;
} else {
    // Chapter found, proceed with view count and navigation
    // Increment view count for the novel
    $novel_id = $chapter['novel_id'] ?? 0;
$stmt_views = $conn->prepare("UPDATE statistics SET views = views + 1 WHERE novel_id = ?");
$stmt_views->execute([$novel_id]);

if ($stmt_views->rowCount() == 0) {
    $insert_stat = $conn->prepare("INSERT INTO statistics (novel_id, views) VALUES (?, 1)");
    $insert_stat->execute([$novel_id]);
}

    // Fetch all chapters for navigation
    $prev_next_query = "
        SELECT id, chapter_number, title
        FROM chapters
        WHERE novel_id = ?
        ORDER BY chapter_number
    ";
    $stmt = $conn->prepare($prev_next_query);
    $stmt->execute([$chapter['novel_id'] ?? 0]);
    $all_chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine prev/next chapter
    $current_index = null;
    $prev_chapter = null;
    $next_chapter = null;

    foreach ($all_chapters as $index => $chap) {
        if ($chap['id'] == $chapter_id) {
            $current_index = $index;
            break;
        }
    }

    if ($current_index !== null) {
        if ($current_index > 0) {
            $prev_chapter = $all_chapters[$current_index - 1];
        }
        if ($current_index < count($all_chapters) - 1) {
            $next_chapter = $all_chapters[$current_index + 1];
        }
    }
}

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($chapter_not_found) ? 'Chapter Not Found' : htmlspecialchars($chapter['title'] ?? ''); ?> - GLOBAL NOVELS</title>
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
        .chapter-header {
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                              url('uploads/covers/<?php echo htmlspecialchars($chapter['cover_image'] ?? ''); ?>');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        .novel-content {
            font-size: 1.1rem;
            line-height: 1.8;
            white-space: pre-line;
        }
        .novel-content p {
            margin-bottom: 1.5rem;
        }
        .chapter-navigation {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <main class="flex-shrink-0">

    <?php if (isset($chapter_not_found)): ?>
        <div class="container mt-5">
            <div class="alert alert-warning text-center">Chapter content coming soon or chapter not found.</div>
        </div>
    <?php else: ?>
        <div class="chapter-header">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h1 class="display-4"><?php echo htmlspecialchars($chapter['novel_title'] ?? ''); ?></h1>
                        <h2 class="display-5">Chapter <?php echo $chapter['chapter_number'] ?? ''; ?>: <?php echo htmlspecialchars($chapter['title'] ?? ''); ?></h2>
                        <p class="lead">By <?php echo htmlspecialchars($chapter['author'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <section class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="novel-content">
                        <?php echo nl2br($chapter['content'] ?? ''); ?>
                    </div>

                    <div class="chapter-navigation d-flex justify-content-between">
                        <?php if ($prev_chapter): ?>
                            <a href="chapter.php?id=<?php echo $prev_chapter['id']; ?>" class="btn btn-secondary">
                                ← Chapter <?php echo $prev_chapter['chapter_number'] ?? ''; ?>: <?php echo htmlspecialchars($prev_chapter['title'] ?? ''); ?>
                            </a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>

                        <a href="novel.php?id=<?php echo $chapter['novel_id']; ?>" class="btn btn-outline-primary">
                            Back to Novel
                        </a>

                        <?php if ($next_chapter): ?>
                            <a href="chapter.php?id=<?php echo $next_chapter['id']; ?>" class="btn btn-primary">
                                Chapter <?php echo $next_chapter['chapter_number'] ?? ''; ?>: <?php echo htmlspecialchars($next_chapter['title'] ?? ''); ?> →
                            </a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
    </main>
    <footer class="bg-dark text-white py-4 mt-auto ">
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
