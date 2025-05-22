<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_POST['novel_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$novel_id = intval($_POST['novel_id']);
$stmt = $conn->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND novel_id = ?');
$stmt->execute([$user_id, $novel_id]);
$is_fav = $stmt->fetchColumn();
if ($is_fav) {
    $del = $conn->prepare('DELETE FROM favorites WHERE user_id = ? AND novel_id = ?');
    $del->execute([$user_id, $novel_id]);
} else {
    $add = $conn->prepare('INSERT INTO favorites (user_id, novel_id) VALUES (?, ?)');
    $add->execute([$user_id, $novel_id]);
}
header('Location: novel.php?id=' . $novel_id);
exit; 