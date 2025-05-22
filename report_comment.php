<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_POST['comment_id']) || !isset($_POST['novel_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$comment_id = intval($_POST['comment_id']);
$novel_id = intval($_POST['novel_id']);
$stmt = $conn->prepare('SELECT 1 FROM comment_reports WHERE user_id = ? AND comment_id = ?');
$stmt->execute([$user_id, $comment_id]);
if (!$stmt->fetchColumn()) {
    $ins = $conn->prepare('INSERT INTO comment_reports (comment_id, user_id) VALUES (?, ?)');
    $ins->execute([$comment_id, $user_id]);
}
header('Location: novel.php?id=' . $novel_id . '#comments');
exit; 