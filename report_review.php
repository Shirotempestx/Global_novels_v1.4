<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_POST['review_id']) || !isset($_POST['novel_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$review_id = intval($_POST['review_id']);
$novel_id = intval($_POST['novel_id']);
$stmt = $conn->prepare('SELECT 1 FROM review_reports WHERE user_id = ? AND review_id = ?');
$stmt->execute([$user_id, $review_id]);
if (!$stmt->fetchColumn()) {
    $ins = $conn->prepare('INSERT INTO review_reports (review_id, user_id) VALUES (?, ?)');
    $ins->execute([$review_id, $user_id]);
    header('Location: novel.php?id=' . $novel_id . '&success=1#reviews');
    exit;
} else {
    header('Location: novel.php?id=' . $novel_id . '&error=1#reviews');
    exit;
} 