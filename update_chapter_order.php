<?php
session_start();
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'author') {
    $data = json_decode(file_get_contents('php://input'), true);
    $author_id = $_SESSION['user_id'];

    foreach ($data as $item) {
        $chapter_id = $item['id'];
        $position = $item['position'];

        $stmt = $conn->prepare("UPDATE chapters SET position = ? WHERE id = ? AND novel_id IN (SELECT id FROM novels WHERE author_id = ?)");
        $stmt->execute([$position, $chapter_id, $author_id]);
    }

    echo "Chapter order updated successfully!";
} else {
    http_response_code(403);
    echo "Unauthorized";
}
