<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    header('Location: banned_users.php?error=missing_user');
    exit();
}

$user_id = intval($_POST['user_id']);

$stmt = $conn->prepare("SELECT * FROM banned_users WHERE user_id = ?");
$stmt->execute([$user_id]);
$banned = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$banned) {
    header('Location: banned_users.php?error=user_not_banned');
    exit();
}

$check = $conn->prepare("SELECT * FROM users WHERE id = ?");
$check->execute([$user_id]);
if ($check->fetch()) {
    header('Location: banned_users.php?error=user_already_exists');
    exit();
}

$defaultPassword = password_hash('ChangeMe123', PASSWORD_DEFAULT);

$insert = $conn->prepare("INSERT INTO users (id, username, email, password, role) VALUES (?, ?, ?, ?, 'reader')");
$success = $insert->execute([
    $banned['user_id'],
    $banned['username'],
    $banned['email'],
    $defaultPassword
]);

if ($success) {
    $del = $conn->prepare("DELETE FROM banned_users WHERE user_id = ?");
    $del->execute([$user_id]);

    header('Location: banned_users.php?success=unbanned');
    exit();
} else {
    header('Location: banned_users.php?error=restore_failed');
    exit();
}
