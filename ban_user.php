<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (empty($_POST['user_id'])) {
    header('Location: manage_users.php?error=missing_user');
    exit();
}

$user_id = intval($_POST['user_id']);
$reason  = trim($_POST['reason'] ?? 'Violation of site rules');

$stmt = $conn->prepare('SELECT id, username, email, role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: manage_users.php?error=user_not_found');
    exit();
}
if ($user['role'] === 'admin') {
    header('Location: manage_users.php?error=cannot_ban_admin');
    exit();
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->beginTransaction();

try {
    $ins = $conn->prepare('
        INSERT INTO banned_users (user_id, username, email, reason)
        VALUES (?, ?, ?, ?)
    ');
    $ins->execute([
        $user['id'],
        $user['username'],
        $user['email'],
        $reason
    ]);

    $del = $conn->prepare('DELETE FROM users WHERE id = ?');
    $del->execute([$user['id']]);

    $conn->commit();
    header('Location: manage_users.php?success=ban');
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    echo '<h1>Ban Failed</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    // error_log($e->getMessage());
    exit;
}
