<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$novel_id = intval($_POST['novel_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
if ($novel_id <= 0 || $content === '') {
    header('Location: novel.php?id=' . $novel_id . '&error=invalid_comment');
    exit();
}
$stmt = $conn->prepare('INSERT INTO comments (novel_id, user_id, content) VALUES (?, ?, ?)');
$stmt->execute([$novel_id, $user_id, $content]);
header('Location: novel.php?id=' . $novel_id . '#comments');
exit; 