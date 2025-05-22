<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit('');
}
$novel_id = intval($_GET['novel_id'] ?? 0);
if ($novel_id <= 0) exit('');
$stmt = $conn->prepare('SELECT * FROM chapters WHERE novel_id = ? ORDER BY chapter_number ASC');
$stmt->execute([$novel_id]);
$chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($chapters)) {
    echo '<div class="alert alert-info">No chapters found.</div>';
    exit;
}
echo '<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Title</th><th>Created At</th><th>Actions</th></tr></thead><tbody>';
foreach ($chapters as $c) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($c['chapter_number']) . '</td>';
    echo '<td>' . htmlspecialchars($c['title']) . '</td>';
    echo '<td>' . htmlspecialchars($c['created_at']) . '</td>';
    echo '<td>';
    echo '<a href="chapter.php?id=' . $c['id'] . '" class="btn btn-sm btn-outline-info">View</a> ';
    echo '<form method="post" action="delete_chapter.php" style="display:inline;">';
    echo '<input type="hidden" name="chapter_id" value="' . $c['id'] . '">';
    echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this chapter?\')">Delete</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table>'; 