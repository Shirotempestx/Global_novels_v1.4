<?php
session_start();
require_once 'connexion.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

// Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_request'])) {
        $request_id = $_POST['request_id'];
        $user_id = $_POST['user_id'];

        // Start transaction
        $conn->beginTransaction();

        try {
            // Update user role to 'author'
            $stmt_update_user = $conn->prepare("UPDATE users SET role = 'author' WHERE id = ?");
            $stmt_update_user->execute([$user_id]);

            // Update request status to 'accepted'
            $stmt_update_request = $conn->prepare("UPDATE author_requests SET status = 'accepted' WHERE id = ?");
            $stmt_update_request->execute([$request_id]);

            $conn->commit();

            // Check if the approved user is the current logged-in admin and update session role
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                $_SESSION['role'] = 'author';
            }

            $message = "<div class='alert alert-success'>Author request approved successfully!</div>";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "<div class='alert alert-danger'>Error approving request: " . $e->getMessage() . "</div>";
        }
    } elseif (isset($_POST['reject_request'])) {
        $request_id = $_POST['request_id'];
        $rejection_reason = $_POST['rejection_reason'] ?? NULL;

        // Update request status to 'rejected' and add rejection reason
        $stmt_update_request = $conn->prepare("UPDATE author_requests SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt_update_request->execute([$rejection_reason, $request_id]);

        $message = "<div class='alert alert-success'>Author request rejected successfully!</div>";
    }
}

// Fetch all pending author requests
$sql = "SELECT ar.id, ar.user_id, u.username, u.email, ar.created_at, ar.reason, ar.novel_idea FROM author_requests ar JOIN users u ON ar.user_id = u.id WHERE ar.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Author Requests - Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reason-cell, .novel-idea-cell {
            max-width: 200px; /* Limit width */
            overflow: auto; /* Add scroll if content overflows */
            word-wrap: break-word; /* Break long words */
        }
    </style>
</head>
<body>
    <?php include 'header.php'; // Assuming you have a header file ?>

    <div class="container mt-4">
        <h2>Manage Author Requests</h2>
        <?php echo $message; // Display messages ?>

        <?php if (!empty($requests)): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Created At</th>
                        <th>Reason</th>
                        <th>Novel Idea</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td class="reason-cell"><?php echo nl2br(htmlspecialchars($row['reason'])); ?></td>
                            <td class="novel-idea-cell"><?php echo nl2br(htmlspecialchars($row['novel_idea'])); ?></td>
                            <td>
                                <form method="POST" action="manage_author_requests.php" style="display:inline-block;">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                    <button type="submit" name="approve_request" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectModal<?php echo $row['id']; ?>">Reject</button>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="rejectModalLabel<?php echo $row['id']; ?>">Reject Request for <?php echo htmlspecialchars($row['username']); ?></h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form method="POST" action="manage_author_requests.php">
                                                <div class="modal-body">
                                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                    <div class="form-group">
                                                        <label for="rejectionReason<?php echo $row['id']; ?>">Reason for Rejection (Optional):</label>
                                                        <textarea class="form-control" id="rejectionReason<?php echo $row['id']; ?>" name="rejection_reason" rows="3"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="reject_request" class="btn btn-danger">Reject Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No pending author requests.</div>
        <?php endif; ?>
    </div>

    <?php // include 'footer.php'; // Assuming you have a footer file ?>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 