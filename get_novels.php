<?php
include 'connexion.php'; 

// 1) Read & sanitize inputs
$status     = $_GET['status']    ?? 'ongoing';
$searchTerm = trim($_GET['query'] ?? '');
$sortField  = $_GET['sortByTime'] ?? 'created_at';

// Only allow these two fields for sorting
$allowedSort = ['created_at', 'updated_at'];
if (!in_array($sortField, $allowedSort, true)) {
    $sortField = 'created_at';
}

// 2) Build base SQL and params
$params = [];
if ($searchTerm !== '') {
    // Search ignores status, but still sorts by time
    $sql = "
        SELECT 
          id,
          title,
          description,
          cover_image,
          created_at,
          updated_at
        FROM novels
        WHERE title       LIKE :search
           OR description LIKE :search
        ORDER BY $sortField DESC
        LIMIT 50
    ";
    $params[':search'] = "%{$searchTerm}%";

} else {
    // No search: filter by status and sort
    $sql = "
        SELECT 
          id,
          title,
          description,
          cover_image,
          created_at,
          updated_at
        FROM novels
        WHERE status = :status
        ORDER BY $sortField DESC
        LIMIT 50
    ";
    $params[':status'] = $status;
}

// 3) Execute
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$novels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Add link attribute
foreach ($novels as &$n) {
    $n['link'] = "novel.php?id=" . $n['id'];
}

// 5) Output JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($novels, JSON_UNESCAPED_SLASHES);
$conn = null;
