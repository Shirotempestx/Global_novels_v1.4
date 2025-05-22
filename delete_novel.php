<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'author') {
    header("Location: login.php");
    exit();
}

$author_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $novel_id = $_GET['id'];

    $stmt = $conn->prepare("SELECT id FROM novels WHERE id = ? AND author_id = ?");
    $stmt->execute([$novel_id, $author_id]);
    if ($stmt->fetch()) {
        $conn->prepare("DELETE FROM chapters WHERE novel_id = ?")->execute([$novel_id]);

        $conn->prepare("DELETE FROM novels WHERE id = ?")->execute([$novel_id]);
    }
}

header("Location: author_dashboard.php");
exit();
?>
