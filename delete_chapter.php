<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'author') {
    header("Location: login.php");
    exit();
}

$chapter_id = $_GET['id'] ?? null;
$author_id = $_SESSION['user_id'];

if (!$chapter_id) {
    header("Location: author_dashboard.php");
    exit();
}

$stmt = $conn->prepare("SELECT c.novel_id FROM chapters c JOIN novels n ON c.novel_id = n.id WHERE c.id = ? AND n.author_id = ?");
$stmt->execute([$chapter_id, $author_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $conn->prepare("DELETE FROM chapters WHERE id = ?")->execute([$chapter_id]);
    header("Location: manage_chapters.php?novel_id=" . $row['novel_id']);
    exit();
} else {
    echo "You do not have permission to delete this chapter.";
}
?>
