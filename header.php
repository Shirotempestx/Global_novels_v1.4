<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'connexion.php'; 

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Check login status and roles
$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    // Fetch the user's current data from the database, including avatar
    $stmt_user = $conn->prepare("SELECT username, role, avatar FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        $username = $user_data['username'];
        $role = $user_data['role'];
        $avatar = $user_data['avatar'];
        // Update session role to reflect database state (important for immediate changes)
        $_SESSION['role'] = $role;
    } else {
        // User not found in DB, maybe delete session or set default role?
        // For now, let's unset session to force re-login
        session_unset();
        session_destroy();
        $is_logged_in = false;
        $username = '';
        $role = '';
        $avatar = '';
    }
} else {
    $user_id = null;
    $username = '';
    $role = '';
    $avatar = '';
}

$is_admin = $role === 'admin';
$is_author = $role === 'author';

$has_pending_request = false;
if ($is_logged_in && $role === 'reader') {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM author_requests WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $has_pending_request = true;
    }
}
?>
<header id="mainHeader" class="bg-dark text-white" style="padding: 30px 0 20px 0; transition: top 0.4s; position: fixed; width: 100%; top: 0; z-index: 1050;">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="display-4 fw-bold text-gradient">GLOBAL NOVELS</h1>
        <nav>
            <button class="d-lg-none navbar-toggler" id="navToggleBtn" aria-label="Toggle navigation" style="background:none;border:none;outline:none;font-size:2rem;color:#fff;">
                <span class="navbar-toggler-icon" style="display:inline-block;width:2em;height:2em;vertical-align:middle;">
                  <svg viewBox="0 0 30 30" width="30" height="30"><rect width="30" height="4" y="4" rx="2" fill="currentColor"/><rect width="30" height="4" y="13" rx="2" fill="currentColor"/><rect width="30" height="4" y="22" rx="2" fill="currentColor"/></svg>
                </span>
            </button>
            <ul class="nav gap-2 align-items-center d-none d-lg-flex" id="mainNavLinks">
                <?php //if ($current_page == 'index.php'): ?>
                    <li class="nav-item"><a href="index.php" class="nav-link btn btn-outline-light rounded-pill px-3 py-2 transition <?php echo $current_page === 'index.php' ? ' active' : ''; ?>">Home</a></li>
                <?php //endif; ?>
                <li class="nav-item"><a href="explore.php" class="nav-link btn btn-outline-light rounded-pill px-3 py-2 transition<?php echo $current_page === 'explore.php' ? ' active' : ''; ?>">Explore</a></li>
                <li class="nav-item"><a href="categories_list.php" class="nav-link btn btn-outline-light rounded-pill px-3 py-2 transition<?php echo $current_page === 'categories_list.php' || $current_page === 'categorie.php' ? ' active' : ''; ?>">Categories</a></li>
                <?php if ($is_logged_in): ?>
                    <?php if ($is_admin): ?>
                        <li class="nav-item"><a href="admin_dashboard.php" class="nav-link btn btn-outline-warning rounded-pill px-3 py-2 transition
                        <?php echo $current_page === 'admin_dashboard.php' 
                        || $current_page === 'admin_dashboard.php' 
                        || $current_page === 'manage_users.php' 
                        || $current_page === 'manage_author_requests.php' 
                        || $current_page === 'banned_users.php' 
                        ||$current_page === 'raports.php' 
                        ||$current_page === 'manage_works.php' ? ' active' : ''; ?>">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($is_author): ?>
                        <li class="nav-item"><a href="author_dashboard.php" class="nav-link btn btn-outline-info rounded-pill px-3 py-2 transition<?php echo $current_page === 'author_dashboard.php' ? ' active' : ''; ?>">Author Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($role === 'reader' && !$has_pending_request): ?>
                        <li class="nav-item"><a href="request_author.php" class="nav-link btn btn-outline-light rounded-pill px-3 py-2 transition<?php echo $current_page === 'request_author.php' ? ' active' : ''; ?>">Request Author Account</a></li>
                    <?php endif; ?>
                    <!-- Account Dropdown -->
                    <li class="nav-item dropdown">
                        <a href="account.php" class="nav-link btn btn-outline-light rounded-pill px-3 py-2 transition d-flex align-items-center <?php echo $current_page === 'account.php' ? ' active' : ''; ?>" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:1.5rem;">
                            <?php if ($is_logged_in ): ?>
                                <img src="uploads/avatars/<?php echo !empty($avatar)? htmlspecialchars($avatar):"default-image.jpg"; ?>" alt="User Avatar" class="user-avatar rounded-circle" style="width: 30px; height: 30px; object-fit: cover;">
                            <?php else: ?>
                                <span title="Account">&#128100;</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                            <li><a class="dropdown-item" href="account.php">Account</a></li>
                            <li><a class="dropdown-item" href="favorites.php">Favorites</a></li>
                            <?php if ($is_author): ?>
                                <li><a class="dropdown-item" href="my_works.php">My Works</a></li>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="raports.php">Manage Reports</a></li>
                                <li><a class="dropdown-item" href="banned_users.php">Banned Users</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a href="login.php" class="nav-link btn btn-outline-light rounded-pill px-3 py-2 transition<?php echo $current_page === 'login.php' ? ' active' : ''; ?>">Login</a></li>
                <?php endif; ?>
            </ul>
            <!-- Mobile menu -->
            <ul class="nav flex-column align-items-start d-lg-none bg-dark p-3 rounded shadow position-absolute w-100" id="mobileNavLinks" style="top:70px;right:0;left:0;display:none;z-index:2000;">
                <?php if ($current_page !== 'index.php'): ?>
                    <li class="nav-item w-100"><a href="index.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2">Home</a></li>
                <?php endif; ?>
                <li class="nav-item w-100"><a href="explore.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2<?php echo $current_page === 'explore.php' ? ' active' : ''; ?>">Explore</a></li>
                <li class="nav-item w-100"><a href="categories_list.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2<?php echo $current_page === 'categories_list.php' ? ' active' : ''; ?>">Categories</a></li>
                <?php if ($is_logged_in): ?>
                    <?php if ($is_admin): ?>
                        <li class="nav-item w-100"><a href="admin_dashboard.php" class="nav-link btn btn-outline-warning rounded-pill w-100 mb-2
                        <?php echo $current_page === 'admin_dashboard.php' 
                        || $current_page === 'admin_dashboard.php' 
                        || $current_page === 'manage_users.php' 
                        || $current_page === 'manage_author_requests.php' 
                        || $current_page === 'banned_users.php' 
                        ||$current_page === 'raports.php' 
                        ||$current_page === 'manage_works.php' ? ' active' : ''; ?>">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($is_author): ?>
                        <li class="nav-item w-100"><a href="author_dashboard.php" class="nav-link btn btn-outline-info rounded-pill w-100 mb-2<?php echo $current_page === 'author_dashboard.php' ? ' active' : ''; ?>">Author Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($role === 'reader' ): ?>
                        <li class="nav-item w-100"><a href="request_author.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2">Request Author Account</a></li>
                    <?php endif; ?>
                    <li class="nav-item w-100"><a href="account.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2">Account</a></li>
                    <li class="nav-item w-100"><a href="favorites.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2">Favorites</a></li>
                    <?php if ($is_author): ?>
                        <li class="nav-item w-100"><a href="my_works.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2">My Works</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item w-100"><a href="raports.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2">Manage Reports</a></li>
                        <li class="nav-item w-100"><a href="banned_users.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2">Banned Users</a></li>
                    <?php endif; ?>
                    <li class="nav-item w-100"><a href="logout.php" class="nav-link btn btn-outline-danger rounded-pill w-100 mb-2">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item w-100"><a href="login.php" class="nav-link btn btn-outline-light rounded-pill w-100 mb-2<?php echo $current_page === 'login.php' ? ' active' : ''; ?>">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<!-- Bootstrap JS for dropdown -->
<script src="js/bootstrap.bundle.min.js"></script>

<style>
    .dropdown:hover .dropdown-menu {
        display: block;
        margin-top: 0;
    }
    .display-4.text-gradient {
        font-size: 2.5rem;
        letter-spacing: 1.5px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        background: linear-gradient(90deg, #ff7e5f, #feb47b);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .nav-link.btn {
        font-weight: 500;
    }
    header .nav-link.btn {
        background: none !important;
        border: 2px solid #fff !important;
        color: #fff !important;
        border-radius: 9999px !important;
        padding: 0.5rem 1.5rem !important;
        margin: 0 0.25rem;
        font-weight: 500;
        transition: background 0.2s, color 0.2s, border 0.2s;
    }
    header .nav-link.btn:hover, header .nav-link.btn.active {
        background: #fff !important;
        color: #222 !important;
        border-color: #ff7e5f !important;
    }
    header .nav-link.btn:focus {
        outline: 2px solid #feb47b;
        outline-offset: 2px;
    }
    header .dropdown-menu {
        background: #23272f;
        border-radius: 0.5rem;
        min-width: 180px;
    }
    header .dropdown-item {
        color: #fff;
    }
    header .dropdown-item:hover {
        background: #ff7e5f;
        color: #fff;
    }
    body {
        padding-top: 100px;
    }
</style>

<script>
    let lastScrollY = window.scrollY;
    const header = document.getElementById('mainHeader');
    window.addEventListener('scroll', function() {
        if (window.scrollY > lastScrollY && window.scrollY > 80) {
            // Scroll Down - Hide header
            header.style.top = '-120px';
        } else {
            // Scroll Up - Show header
            header.style.top = '0';
        }
        lastScrollY = window.scrollY;
    });
    // Responsive nav toggle
    const navToggleBtn = document.getElementById('navToggleBtn');
    const mobileNavLinks = document.getElementById('mobileNavLinks');
    navToggleBtn && navToggleBtn.addEventListener('click', function() {
        if (mobileNavLinks.style.display === 'none' || !mobileNavLinks.style.display) {
            mobileNavLinks.style.display = 'block';
        } else {
            mobileNavLinks.style.display = 'none';
        }
    });
    // Hide mobile nav on resize to large
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            mobileNavLinks.style.display = 'none';
        }
    });
</script> 