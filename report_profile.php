<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_POST['profile_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$profile_id = intval($_POST['profile_id']);
$stmt = $conn->prepare('SELECT 1 FROM profile_reports WHERE user_id = ? AND profile_id = ?');
$stmt->execute([$user_id, $profile_id]);
if (!$stmt->fetchColumn()) {
    $ins = $conn->prepare('INSERT INTO profile_reports (profile_id, user_id) VALUES (?, ?)');
    $ins->execute([$profile_id, $user_id]);
    header('Location: account.php?id=' . $profile_id . '&success=1');
    exit;
} else {
    header('Location: account.php?id=' . $profile_id . '&error=1');
    exit;
} 