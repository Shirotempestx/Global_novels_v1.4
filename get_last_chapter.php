<?php
require_once 'connexion.php';

header('Content-Type: application/json');

if (!isset($_GET['novel_id'])) {
    echo json_encode(['error' => 'Novel ID is required']);
    exit();
}

$novel_id = $_GET['novel_id'];

try {
    $stmt = $conn->prepare("SELECT MAX(chapter_number) AS last_chapter FROM chapters WHERE novel_id = ?");
    $stmt->execute([$novel_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'last_chapter' => $data['last_chapter'] !== null ? (int)$data['last_chapter'] : null
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}


?>
