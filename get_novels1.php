<?php
include 'connexion.php'; 

$status = $_GET['status'] ?? 'ongoing';
$searchQuery = $_GET['query'] ?? '';

if (!empty($searchQuery)) {
    $sql = "SELECT id, title, description AS `desc`, cover_image
            FROM novels 
            WHERE title LIKE :search OR description LIKE :search";

    $stmt = $conn->prepare($sql);
    $searchTerm = "%$searchQuery%";
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add links
    foreach ($results as &$novel) {
        $novel['link'] = "novel.php?id=" . ($novel['id'] ?? '');
    }

    echo json_encode($results);
    exit;
}

if ($status === "popular") {
    $sql = "SELECT id, title, description AS `desc`, cover_image AS img FROM novels LIMIT 9";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
} else {
    $sql = "SELECT id, title, description AS `desc`, cover_image AS img FROM novels WHERE status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$status]);
}

// if (!empty($_GET['for'])){
//     $carousel = True;
// }
// if ($carousel){
//     $sql = "SELECT id, title, description AS `desc`, cover_image AS img FROM novels LIMIT 3";
//     $stmt = $conn->prepare($sql);
//     $stmt->execute();
// }

$novels = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($novels as &$novel) {
    $novel['link'] = "novel.php?id=" . ($novel['id'] ?? '');
}

header('Content-Type: application/json');
echo json_encode($novels);
$conn = null;

?>
