<?php
// fetch_chapters.php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='author') {
  header('HTTP/1.1 403 Forbidden');
  exit('Forbidden');
}

$author_id = $_SESSION['user_id'];
$novel_id  = intval($_GET['novel_id'] ?? 0);

if (!$novel_id) {
  header('HTTP/1.1 400 Bad Request');
  exit('Bad Request');
}

// Verify ownership
$chk = $conn->prepare("SELECT id FROM novels WHERE id=? AND author_id=?");
$chk->execute([$novel_id, $author_id]);
if (!$chk->fetch()) {
  header('HTTP/1.1 403 Forbidden');
  exit('Forbidden');
}

// Fetch chapters
$stmt = $conn->prepare("
  SELECT id, chapter_number, title 
  FROM chapters
  WHERE novel_id=?
  ORDER BY position ASC, chapter_number ASC
");
$stmt->execute([$novel_id]);
$chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($chapters);
exit;
