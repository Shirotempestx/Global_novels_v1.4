<?php
session_start();
require_once 'connexion.php';

// 1) Only logged‑in authors can add chapters
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header("Location: login.php");
    exit();
}
$author_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2) Get & sanitize POST data
    $novel_id       = intval($_POST['novel_id'] ?? 0);
    $chapter_title  = trim($_POST['chapter_title']  ?? '');
    $chapter_number = intval($_POST['chapter_number'] ?? 0);
    $content        = trim($_POST['content']        ?? '');

    // 3) Simple validation
    if ($novel_id <= 0 || !$chapter_title || $chapter_number <= 0 || !$content) {
        // Redirect back with error
        header("Location: author_dashboard.php?add_chapter_error=1");
        exit();
    }

    // 4) Verify novel ownership
    $check = $conn->prepare("
        SELECT id 
        FROM novels 
        WHERE id = ? AND author_id = ?
        LIMIT 1
    ");
    $check->execute([$novel_id, $author_id]);
    if (!$check->fetch()) {
        // Not allowed to add to someone else's novel
        header("HTTP/1.1 403 Forbidden");
        echo "You do not have permission to add chapters to this novel.";
        exit();
    }

    // 5) Determine next position (optional)
    $posStmt = $conn->prepare("
        SELECT COALESCE(MAX(position), 0) + 1 AS next_pos
        FROM chapters
        WHERE novel_id = ?
    ");
    $posStmt->execute([$novel_id]);
    $nextPos = (int)$posStmt->fetchColumn();

    // 6) Insert the new chapter
    $insert = $conn->prepare("
        INSERT INTO chapters
          (novel_id, title, content, chapter_number, position, created_at)
        VALUES
          (?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute([
        $novel_id,
        $chapter_title,
        $content,
        $chapter_number,
        $nextPos
    ]);

    // 7) Redirect back to dashboard with success
    header("Location: author_dashboard.php?add_chapter_success=1");
    exit();
}

// If someone tries to GET this file, just redirect
header("Location: author_dashboard.php");
exit();
