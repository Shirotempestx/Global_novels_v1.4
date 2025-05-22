<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body { background: #f5f6fa; min-height: 100vh; padding-top: 110px; }
    .admin-card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 2px 16px rgba(0,0,0,0.07);
      border: 1px solid #ececec;
      margin-bottom: 2rem;
      padding: 2rem 1.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 220px;
      transition: box-shadow 0.2s, transform 0.2s;
      cursor: pointer;
    }
    .admin-card:hover {
      box-shadow: 0 6px 24px rgba(0,0,0,0.13);
      transform: translateY(-4px) scale(1.03);
    }
    .admin-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      color: #0b5ed7;
    }
    .admin-title {
      font-size: 1.3rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }
    .admin-desc {
      color: #555;
      font-size: 1rem;
      text-align: center;
    }
    @media (max-width: 900px) {
      .row { flex-direction: column; }
      .admin-card { margin-bottom: 1.5rem; }
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Admin Dashboard</h2>
  <div class="row g-4 justify-content-center">
    <div class="col-md-4">
      <a href="manage_works.php" class="text-decoration-none">
        <div class="admin-card">
          <div class="admin-icon"><i class="fa fa-book"></i></div>
          <div class="admin-title">Manage Works</div>
          <div class="admin-desc">Review, edit, or delete all novels and chapters.</div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="raports.php" class="text-decoration-none">
        <div class="admin-card">
          <div class="admin-icon"><i class="fa fa-flag"></i></div>
          <div class="admin-title">Manage Reports</div>
          <div class="admin-desc">View and handle all user, review, and novel reports.</div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="banned_users.php" class="text-decoration-none">
        <div class="admin-card">
          <div class="admin-icon"><i class="fa fa-user-slash"></i></div>
          <div class="admin-title">Banned Users</div>
          <div class="admin-desc">View and manage banned accounts.</div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="manage_users.php" class="text-decoration-none">
        <div class="admin-card">
          <div class="admin-icon"><i class="fa fa-users"></i></div>
          <div class="admin-title">Manage Users</div>
          <div class="admin-desc">View, search, and manage all users.</div>
        </div>
      </a>
    </div>
    <div class="col-md-4 ">
      <a href="manage_author_requests.php" class="text-decoration-none">
        <div class="admin-card">
          <div class="admin-icon"><i class="fa fa-user-plus"></i></div>
          <div class="admin-title">Author Requests</div>
          <div class="admin-desc">Review and process author account requests.</div>
        </div>
      </a>
    </div>
  </div>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
