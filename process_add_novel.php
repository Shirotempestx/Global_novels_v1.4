<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header("Location: login.php");
    exit();
}

$author_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Grab & sanitize your POST data
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type    = $_POST['type'] ?? 'original';
    $status      = $_POST['status']   ?? 'ongoing';
    $cats_json   = $_POST['categories_json'] ?? '[]';
    $categories  = json_decode($cats_json, true);
    
    // 2) Handle cover upload (optional)
    $cover_image = null;
    if (!empty($_FILES['cover_image']['tmp_name']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $ext;
        $dest = __DIR__ . '/uploads/covers/' . $newName;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
            $cover_image = $newName;
        }
    }

    // 3) Insert into `novels`  
    //    — notice we've removed `updated_at` since your table doesn't have that column
    $stmt = $conn->prepare("
        INSERT INTO novels 
          (author_id, title, description, type, status, cover_image, created_at)
        VALUES 
          (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $author_id,
        $title,
        $description,
        $type,
        $status,
        $cover_image
    ]);
    $novel_id = $conn->lastInsertId();
    $statistic = $conn->prepare("INSERT INTO statistics (novel_id) VALUES (?)");
    $statistic->execute([$novel_id]);

    // 4) Insert into pivot `novel_categories`
    if (is_array($categories) && count($categories) > 0) {
        $pc = $conn->prepare("
            INSERT INTO novel_categories (novel_id, category_id) 
            VALUES (?, ?)
        ");
        foreach ($categories as $cat_id) {
            $pc->execute([$novel_id, $cat_id]);
        }
    }

    // 5) Redirect back to dashboard
    header("Location: author_dashboard.php?success=1");
    exit;
}

// if we get here, it wasn’t a POST
header("Location: author_dashboard.php");
exit;
