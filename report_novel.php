<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_POST['novel_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$novel_id = intval($_POST['novel_id']);
$reason = trim($_POST['reason'] ?? '');
$stmt = $conn->prepare('SELECT 1 FROM novel_reports WHERE user_id = ? AND novel_id = ?');
$stmt->execute([$user_id, $novel_id]);
if (!$stmt->fetchColumn()) {
    $ins = $conn->prepare('INSERT INTO novel_reports (novel_id, user_id, reason) VALUES (?, ?, ?)');
    $ins->execute([$novel_id, $user_id, $reason]);
    header('Location: novel.php?id=' . $novel_id . '&success=1');
    exit;
} else {
    header('Location: novel.php?id=' . $novel_id . '&error=1');
    exit;
} 