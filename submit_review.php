<?php
session_start();
require_once 'connexion.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$novel_id = isset($_POST['novel_id']) ? intval($_POST['novel_id']) : 0;
$rating   = isset($_POST['rating'])   ? intval($_POST['rating'])   : 0;
$comment  = isset($_POST['comment'])  ? trim($_POST['comment'])    : '';

if ($novel_id <= 0 || $rating < 1 || $rating > 5) {
    header("Location: novel.php?id={$novel_id}&error=invalid_input");
    exit();
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO ratings (user_id, novel_id, rating, comment, created_at)
        VALUES (:user_id, :novel_id, :rating, :comment, NOW())
    ");
    $stmt->execute([
        ':user_id'  => $_SESSION['user_id'],
        ':novel_id' => $novel_id,
        ':rating'   => $rating,
        ':comment'  => $comment
    ]);

    $statsStmt = $conn->prepare("
        SELECT
            COUNT(*)       AS cnt,
            AVG(rating)    AS avg_rating
        FROM ratings
        WHERE novel_id = :novel_id
    ");
    $statsStmt->execute([':novel_id' => $novel_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $count      = (int)   $stats['cnt'];
    $avg_rating = (float) $stats['avg_rating'];

    $upsert = $conn->prepare("
        INSERT INTO statistics (novel_id, ratings_count, average_rating)
        VALUES (:novel_id, :cnt, :avg_rating)
        ON DUPLICATE KEY UPDATE
            ratings_count   = VALUES(ratings_count),
            average_rating  = VALUES(average_rating)
    ");
    $upsert->execute([
        ':novel_id'   => $novel_id,
        ':cnt'        => $count,
        ':avg_rating' => $avg_rating
    ]);

    $conn->commit();

    header("Location: novel.php?id={$novel_id}&review_submitted=1");
    exit();

} catch (PDOException $e) {
    $conn->rollBack();

    header("Location: novel.php?id={$novel_id}&error=review_failed");
    exit();
}
?>
