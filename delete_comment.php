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
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$stmt = $conn->prepare('SELECT user_id FROM comments WHERE id = ?');
$stmt->execute([$comment_id]);
$owner_id = $stmt->fetchColumn();
if ($owner_id == $user_id || $is_admin) {
    $del = $conn->prepare('DELETE FROM comments WHERE id = ?');
    $del->execute([$comment_id]);
}
header('Location: novel.php?id=' . $novel_id . '#comments');
exit; 