<?php
session_start();
require_once 'connexion.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Check if review_id and novel_id are provided via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review_id']) && isset($_POST['novel_id'])) {
    $review_id = intval($_POST['review_id']);
    $novel_id = intval($_POST['novel_id']);

    // Fetch the review to check ownership
    $stmt = $conn->prepare("SELECT user_id FROM ratings WHERE id = ?");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        // Review not found, redirect back to novel page
        header("Location: novel.php?id=" . $novel_id . "&error=review_not_found");
        exit();
    }

    // Check if the logged-in user is the review author or an admin
    if ($user_id == $review['user_id'] || $is_admin) {
        try {
            // Start transaction for safety
            $conn->beginTransaction();

            // Delete the review
            $delete_stmt = $conn->prepare("DELETE FROM ratings WHERE id = ?");
            $delete_stmt->execute([$review_id]);

            // Recalculate novel statistics (ratings count and average)
            $stats_stmt = $conn->prepare("SELECT COUNT(*) as ratings_count, SUM(rating) as ratings_sum FROM ratings WHERE novel_id = ?");
            $stats_stmt->execute([$novel_id]);
            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

            $new_ratings_count = $stats['ratings_count'] ?? 0;
            $new_ratings_sum = $stats['ratings_sum'] ?? 0;
            $new_average_rating = $new_ratings_count > 0 ? $new_ratings_sum / $new_ratings_count : 0;

            $update_stats_stmt = $conn->prepare("UPDATE statistics SET ratings_count = ?, average_rating = ? WHERE novel_id = ?");
            $update_stats_stmt->execute([$new_ratings_count, $new_average_rating, $novel_id]);

            $conn->commit();

            // Redirect back to novel page with success message
            header("Location: novel.php?id=" . $novel_id . "&success=review_deleted");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            // Log the error if necessary
            // error_log("Review deletion failed: " . $e->getMessage());
            // Redirect back to novel page with error message
            header("Location: novel.php?id=" . $novel_id . "&error=deletion_failed");
            exit();
        }
    } else {
        // Not authorized to delete this review, redirect back
        header("Location: novel.php?id=" . $novel_id . "&error=unauthorized");
        exit();
    }

} else {
    // Invalid request, redirect back to index or novel page
    header("Location: index.php");
    exit();
}
?> 