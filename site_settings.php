<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
// Fetch settings
$settings = [];
$stmt = $conn->prepare('SELECT * FROM settings');
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}
$site_name = $settings['site_name'] ?? 'GLOBAL NOVELS';
$site_desc = $settings['site_desc'] ?? 'The best place to read and share novels.';
$site_email = $settings['site_email'] ?? 'global0novels@gmail.com';
$site_logo = $settings['site_logo'] ?? 'logo.png';
// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_settings = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_desc' => trim($_POST['site_desc'] ?? ''),
        'site_email' => trim($_POST['site_email'] ?? ''),
    ];
    // Handle logo upload
    if (!empty($_FILES['site_logo']['name'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid() . '-' . basename($_FILES['site_logo']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $targetFile)) {
            $new_settings['site_logo'] = $fileName;
        } else {
            $error = 'Failed to upload logo.';
        }
    }
    if (!isset($error)) {
        foreach ($new_settings as $key => $value) {
            $stmt = $conn->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?');
            $stmt->execute([$key, $value, $value]);
        }
        $success = 'Settings saved successfully.';
        // Refresh settings after save
        $settings = [];
        $stmt = $conn->prepare('SELECT * FROM settings');
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
        $site_name = $settings['site_name'] ?? 'GLOBAL NOVELS';
        $site_desc = $settings['site_desc'] ?? 'The best place to read and share novels.';
        $site_email = $settings['site_email'] ?? 'global0novels@gmail.com';
        $site_logo = $settings['site_logo'] ?? 'logo.png';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Site Settings - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Site Settings</h2>
  <?php if (isset($success)): ?>
    <div class="alert alert-success text-center"><?= $success ?></div>
  <?php elseif (isset($error)): ?>
    <div class="alert alert-danger text-center"><?= $error ?></div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="mx-auto" style="max-width:500px;">
    <div class="mb-3">
      <label class="form-label">Site Name</label>
      <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($site_name) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Site Description</label>
      <textarea name="site_desc" class="form-control" rows="2"><?= htmlspecialchars($site_desc) ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Contact Email</label>
      <input type="email" name="site_email" class="form-control" value="<?= htmlspecialchars($site_email) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Site Logo</label><br>
      <img src="uploads/<?= htmlspecialchars($site_logo) ?>" alt="Logo" style="max-width:120px;max-height:80px;" class="mb-2"><br>
      <input type="file" name="site_logo" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Save Settings</button>
  </form>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 