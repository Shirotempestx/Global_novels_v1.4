<?php
session_start();
require_once 'connexion.php';

// Determine which user profile to show
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['user_id'] ?? 0);
$is_own_profile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_id;

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) die('User not found');

if ($is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Email
    if (isset($_POST['email'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $profile_id]);
        $profile['email'] = $email;
    }
    // Password
    if (!empty($_POST['old_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$profile_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && password_verify($_POST['old_password'], $row['password'])) {
                $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_hash, $profile_id]);
            }
        }
    }
    // Avatar
    if (!empty($_FILES['avatar']['name'])) {
        $avatar = uploadFile($_FILES['avatar'], 'avatars');
        if ($avatar) {
            $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$avatar, $profile_id]);
            $profile['avatar'] = $avatar;
        }
    }
    // Banner/Cover
    if (!empty($_FILES['cover_image']['name'])) {
        $cover = uploadFile($_FILES['cover_image'], 'covers');
        if ($cover) {
            $stmt = $conn->prepare("UPDATE users SET cover_image = ? WHERE id = ?");
            $stmt->execute([$cover, $profile_id]);
            $profile['cover_image'] = $cover;
        }
    }
    // Refresh page to show changes
    header("Location: account.php" . ($is_own_profile ? '' : '?id=' . $profile_id));
    exit();
}

function uploadFile($file, $folder) {
    $targetDir = "uploads/$folder/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = uniqid() . '-' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $check = getimagesize($file['tmp_name']);
    if ($check === false) return false;
    if ($file['size'] > 2000000) return false;
    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) return false;
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $fileName;
    }
    return false;
}

// Fetch favorites, works, ratings
$favorites_stmt = $conn->prepare("
    SELECT n.*, u.username as author_name
    FROM novels n
    JOIN favorites f ON n.id = f.novel_id
    JOIN users u ON n.author_id = u.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$favorites_stmt->execute([$profile_id]);
$favorites = $favorites_stmt->fetchAll(PDO::FETCH_ASSOC);

$works_stmt = $conn->prepare("
    SELECT * FROM novels WHERE author_id = ? ORDER BY created_at DESC
");
$works_stmt->execute([$profile_id]);
$works = $works_stmt->fetchAll(PDO::FETCH_ASSOC);

$ratings_stmt = $conn->prepare("
    SELECT r.*, n.title, n.cover_image FROM ratings r
    JOIN novels n ON r.novel_id = n.id
    WHERE r.user_id = ? ORDER BY r.created_at DESC
");
$ratings_stmt->execute([$profile_id]);
$ratings = $ratings_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($profile['username']) ?>'s Account | Global Novels</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="dark">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Roboto', Arial, sans-serif; background: #181c20; color: #f4f4f5; padding-top: 120px; }
    main { min-height: 80vh; margin-top: 1.5rem; }
    .tab-btn.active, .tab-btn:focus { background-color: #374151; color: #efefef; }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn .4s; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(8px);} to { opacity: 1; transform: none;} }
    input:disabled, select:disabled { background: #21242a; color: #6b7280; cursor: not-allowed; }
    ::-webkit-scrollbar {width: 8px;} ::-webkit-scrollbar-thumb {background: #23272f;}
    .avatar-edit-btn, .banner-edit-btn { cursor: pointer;}
    .banner-img { object-fit: cover; height: 150px; width: 100%; }
    .container, main { padding-left: 1rem; padding-right: 1rem; }
    @media (max-width:1023px) {
      .flex-row-lg { flex-direction: column; }
      .h-full-lg { height: auto; }
      .tab-bar-scroll { overflow-x: auto; white-space: nowrap; }
    }
    @media (max-width: 600px) {
      .tab-btn { min-width: 160px; font-size: 0.95rem; }
      .tab-bar-scroll { overflow-x: auto; white-space: nowrap; }
      .w-full, .max-w-xs { max-width: 100% !important; }
    }
  </style>
</head>
<body class="bg-gray-900">
<?php
include 'header.php';
?>
  <main class="container mx-auto my-8 px-4">
    <div class="flex flex-row gap-8 flex-row-lg h-full-lg">
      <section class="w-full max-w-xs bg-gray-800 rounded-xl shadow-lg flex-shrink-0 p-6 relative mb-6 md:mb-0">
        <!-- Banner -->
        <div class="relative mb-6">
          <img id="bannerImg" src="uploads/covers/<?= htmlspecialchars($profile['cover_image'] ?? 'default-cover.jpg') ?>" alt="Profile Banner" class="banner-img rounded-t-lg w-full">
          <?php if ($is_own_profile): ?>
          <form method="post" enctype="multipart/form-data" class="absolute top-2 right-2">
            <input type="file" name="cover_image" accept="image/*" class="hidden" id="bannerInput" onchange="this.form.submit()">
            <label for="bannerInput" class="bg-black/50 rounded-full p-2 hover:bg-black/70 banner-edit-btn cursor-pointer" title="Change banner">
              <i class="fa fa-camera text-lg text-white"></i>
            </label>
          </form>
          <?php endif; ?>
        </div>
        <!-- Avatar -->
        <div class="flex flex-col items-center">
          <div class="relative">
            <img id="avatarImg" src="uploads/avatars/<?= htmlspecialchars($profile['avatar'] ?? 'default-avatar.jpg') ?>" class="w-28 h-28 object-cover rounded-full border-4 border-gray-900 shadow mb-3" alt="User Avatar">
            <?php if ($is_own_profile): ?>
            <form method="post" enctype="multipart/form-data" class="absolute bottom-3 right-2">
              <input type="file" name="avatar" accept="image/*" class="hidden" id="avatarInput" onchange="this.form.submit()">
              <label for="avatarInput" class="bg-gray-700 rounded-full p-2 hover:bg-gray-600 avatar-edit-btn cursor-pointer" title="Change avatar">
                <i class="fa fa-camera text-sm text-white"></i>
              </label>
            </form>
            <?php endif; ?>
          </div>
          <h2 id="profileUsername" class="text-xl font-bold mt-1 text-gray-50 tracking-wide"><?= htmlspecialchars($profile['username']) ?></h2>
          <span class="text-xs text-gray-300 mt-1"><?= ucfirst($profile['role']) ?></span>
          <div class="mt-4 flex-col flex items-center">
            <span class="font-semibold text-xl text-indigo-400" id="favoriteCount"><?= count($favorites) ?></span>
            <span class="text-gray-400 text-xs">Favorites</span>
          </div>
        </div>
      </section>
      <!-- Main Tabbed Content -->
      <section class="flex-1 bg-gray-800 rounded-xl shadow-lg py-4 px-6 overflow-x-auto">
        <!-- Tabs -->
        <div class="flex gap-2 mb-4 border-b border-gray-700 tab-bar-scroll">
          <button class="tab-btn px-4 py-2 rounded-t-md font-semibold focus:outline-none transition active" data-tab="info">
            <i class="fa fa-user-circle mr-1"></i>Account Info
          </button>
          <button class="tab-btn px-4 py-2 rounded-t-md font-semibold focus:outline-none transition" data-tab="favorites">
            <i class="fa fa-heart mr-1"></i>Favorite Novels
          </button>
          <button class="tab-btn px-4 py-2 rounded-t-md font-semibold focus:outline-none transition" data-tab="myworks">
            <i class="fa fa-feather-pointed mr-1"></i>My Works
          </button>
          <button class="tab-btn px-4 py-2 rounded-t-md font-semibold focus:outline-none transition" data-tab="ratings">
            <i class="fa fa-star mr-1"></i>Ratings
          </button>
        </div>
        <!-- Tab contents -->
        <div class="tab-content active" id="tab-info">
          <h3 class="text-lg font-bold mb-4">Personal Information</h3>
          <?php if ($is_own_profile): ?>
          <form class="space-y-6" id="accountForm" autocomplete="off" method="post" enctype="multipart/form-data">
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block mb-1 text-sm font-semibold">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($profile['username']) ?>" class="block w-full px-3 py-2 rounded-md bg-gray-700 text-gray-200 border border-gray-600" disabled>
              </div>
              <div class="flex-1">
                <label class="block mb-1 text-sm font-semibold">Role</label>
                <input type="text" name="role" value="<?= ucfirst($profile['role']) ?>" class="block w-full px-3 py-2 rounded-md bg-gray-700 text-gray-300 border border-gray-600" disabled>
              </div>
            </div>
            <div>
              <label class="block mb-1 text-sm font-semibold">Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" class="block w-full px-3 py-2 rounded-md bg-gray-700 text-gray-200 border border-gray-600" required>
            </div>
            <div>
              <label class="block mb-1 text-sm font-semibold">Change Password</label>
              <div class="flex gap-2">
                <input type="password" name="old_password" class="w-1/3 px-3 py-2 rounded-md bg-gray-700 text-gray-200 border border-gray-600" placeholder="Current Password">
                <input type="password" name="new_password" class="w-1/3 px-3 py-2 rounded-md bg-gray-700 text-gray-200 border border-gray-600" placeholder="New Password">
                <input type="password" name="confirm_password" class="w-1/3 px-3 py-2 rounded-md bg-gray-700 text-gray-200 border border-gray-600" placeholder="Confirm New Password">
              </div>
              <small class="text-gray-500">Leave blank if not changing password.</small>
            </div>
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block mb-1 text-sm font-semibold">Avatar</label>
                <div class="flex items-center gap-2">
                  <img id="formAvatarImg" src="uploads/avatars/<?= htmlspecialchars($profile['avatar'] ?? 'default-avatar.jpg') ?>" class="w-12 h-12 rounded-full border border-gray-700 object-cover" alt="Avatar Preview">
                  <input type="file" accept="image/*" id="avatarInputForm" class="hidden">
                  <button type="button" onclick="document.getElementById('avatarInputForm').click()" class="px-3 py-1 bg-gray-700 rounded-md text-sm hover:bg-gray-600 transition flex items-center gap-1">
                    <i class="fa fa-camera"></i> Change
                  </button>
                </div>
              </div>
              <div class="flex-1">
                <label class="block mb-1 text-sm font-semibold">Banner</label>
                <div class="flex items-center gap-2">
                  <img id="formBannerImg" src="uploads/covers/<?= htmlspecialchars($profile['cover_image'] ?? 'default-cover.jpg') ?>" class="h-12 w-28 rounded border border-gray-700 object-cover" alt="Banner Preview">
                  <input type="file" accept="image/*" id="bannerInputForm" class="hidden">
                  <button type="button" onclick="document.getElementById('bannerInputForm').click()" class="px-3 py-1 bg-gray-700 rounded-md text-sm hover:bg-gray-600 transition flex items-center gap-1">
                    <i class="fa fa-camera"></i> Change
                  </button>
                </div>
              </div>
            </div>
            <div>
              <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 transition text-white px-6 py-2 rounded-md font-bold shadow">Save Changes</button>
              <span id="formMessage" class="ml-4 text-green-400 text-sm"></span>
            </div>
          </form>
          <?php else: ?>
          <div class="space-y-4">
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block mb-1 text-sm font-semibold">Username</label>
                <input type="text" value="<?= htmlspecialchars($profile['username']) ?>" class="block w-full px-3 py-2 rounded-md bg-gray-700 text-gray-200 border border-gray-600" disabled>
              </div>
              <div class="flex-1">
                <label class="block mb-1 text-sm font-semibold">Role</label>
                <input type="text" value="<?= ucfirst($profile['role']) ?>" class="block w-full px-3 py-2 rounded-md bg-gray-700 text-gray-300 border border-gray-600" disabled>
              </div>
            </div>
            <div>
              <label class="block mb-1 text-sm font-semibold">Email</label>
              <input type="email" value="<?= htmlspecialchars($profile['email']) ?>" class="block w-full px-3 py-2 rounded-md bg-gray-700 text-gray-200 border border-gray-600" disabled>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <!-- Favorite Novels Tab -->
        <div class="tab-content" id="tab-favorites">
          <h3 class="text-lg font-bold mb-4">Favorite Novels</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($favorites as $novel): ?>
            <div class="bg-gray-700 rounded-lg overflow-hidden flex flex-row shadow">
              <img src="uploads/covers/<?= htmlspecialchars($novel['cover_image'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($novel['title']) ?>" class="w-24 h-32 object-cover rounded-l-lg">
              <div class="flex-1 p-3 flex flex-col justify-between">
                <div>
                  <h4 class="font-semibold text-indigo-200"><?= htmlspecialchars($novel['title']) ?></h4>
                  <div class="text-xs text-gray-400 mb-1">by <span class="font-mono"><?= htmlspecialchars($novel['author_name']) ?></span></div>
                  <p class="text-sm text-gray-300 truncate"><?= htmlspecialchars(mb_strimwidth($novel['description'], 0, 80, '...')) ?></p>
                </div>
                <div class="flex items-center mt-2 gap-1">
                  <span class="fa fa-star text-yellow-400"></span>
                  <span class="text-xs text-gray-300">Status: <?= ucfirst($novel['status']) ?></span>
                  <a href="novel.php?id=<?= $novel['id'] ?>" class="ml-auto text-indigo-400 hover:underline">Read</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($favorites)): ?>
            <div class="text-gray-400">No favorite novels yet.</div>
            <?php endif; ?>
          </div>
        </div>
        <!-- My Works Tab -->
        <div class="tab-content" id="tab-myworks">
          <h3 class="text-lg font-bold mb-4">My Works</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($works as $novel): ?>
            <div class="bg-gray-700 rounded-lg overflow-hidden flex flex-row shadow">
              <img src="uploads/covers/<?= htmlspecialchars($novel['cover_image'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($novel['title']) ?>" class="w-24 h-32 object-cover rounded-l-lg">
              <div class="flex-1 p-3 flex flex-col justify-between">
                <div>
                  <h4 class="font-semibold text-indigo-200"><?= htmlspecialchars($novel['title']) ?></h4>
                  <div class="text-xs text-gray-400 mb-1">Status: <?= ucfirst($novel['status']) ?></div>
                  <p class="text-sm text-gray-300 truncate"><?= htmlspecialchars(mb_strimwidth($novel['description'], 0, 80, '...')) ?></p>
                </div>
                <div class="flex items-center mt-2 gap-1">
                  <a href="novel.php?id=<?= $novel['id'] ?>" class="ml-auto text-indigo-400 hover:underline">View</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($works)): ?>
            <div class="text-gray-400">No works yet.</div>
            <?php endif; ?>
          </div>
        </div>
        <!-- Ratings Tab -->
        <div class="tab-content" id="tab-ratings">
          <h3 class="text-lg font-bold mb-4">Ratings</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($ratings as $rating): ?>
            <div class="bg-gray-700 rounded-lg overflow-hidden flex flex-row shadow">
              <img src="uploads/covers/<?= htmlspecialchars($rating['cover_image'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($rating['title']) ?>" class="w-24 h-32 object-cover rounded-l-lg">
              <div class="flex-1 p-3 flex flex-col justify-between">
                <div>
                  <h4 class="font-semibold text-indigo-200"><?= htmlspecialchars($rating['title']) ?></h4>
                  <div class="text-xs text-gray-400 mb-1">Rated: <?= $rating['rating'] ?>/5</div>
                  <p class="text-sm text-gray-300 truncate">Rated on <?= date('M j, Y', strtotime($rating['created_at'])) ?></p>
                </div>
                <div class="flex items-center mt-2 gap-1">
                  <a href="novel.php?id=<?= $rating['novel_id'] ?>" class="ml-auto text-indigo-400 hover:underline">View</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($ratings)): ?>
            <div class="text-gray-400">No ratings yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </div>
  </main>
  <script>
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
      });
    });
  </script>

<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-…"
  crossorigin="anonymous"
></script>

<?php if ($is_logged_in && $profile_id != $_SESSION['user_id'] && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')): ?>
    <form method="post" action="report_profile.php" class="mt-4 text-center">
        <input type="hidden" name="profile_id" value="<?= $profile_id ?>">
        <button type="submit" class="btn btn-outline-warning">Report this profile</button>
    </form>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success text-center">Report submitted successfully.</div>
<?php elseif (isset($_GET['error'])): ?>
  <div class="alert alert-danger text-center">You have already reported this profile.</div>
<?php endif; ?>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $profile_id != $_SESSION['user_id']): ?>
    <?php
    $ban_check = $conn->prepare('SELECT 1 FROM banned_users WHERE user_id = ?');
    $ban_check->execute([$profile_id]);
    if (!$ban_check->fetchColumn()): ?>
        <form method="post" action="ban_user.php" class="mt-4 text-center d-flex justify-content-center align-items-center">
            <input type="hidden" name="user_id" value="<?= $profile_id ?>">
            <input type="text" name="reason" class="form-control form-control-sm me-2" placeholder="Ban reason (optional)" style="max-width:200px;display:inline-block;">
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ban this user?')">Ban User</button>
        </form>
    <?php endif; ?>
<?php endif; ?>

</body>
</html> 