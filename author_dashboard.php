<?php
session_start();
require_once 'connexion.php';

// -----------------------------------------------------------------------
// 1) AJAX endpoint for live category search 
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_category_search'])) {
    $term    = '%' . trim($_POST['term'] ?? '') . '%';
    $exclude = $_POST['exclude'] ?? [];
    if (!is_array($exclude)) $exclude = [];

    // Build SQL
    $sql    = "SELECT id, name FROM categories WHERE name LIKE ?";
    $params = [$term];

    if (count($exclude) > 0) {
        // Add placeholders for excluded IDs
        $placeholders = implode(',', array_fill(0, count($exclude), '?'));
        $sql         .= " AND id NOT IN ($placeholders)";
        $params       = array_merge($params, $exclude);
    }

    $sql .= " ORDER BY name LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// -------------------------------------------------
// -------------------------------------------------
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header("Location: login.php");
    exit();
}

$author_id = $_SESSION['user_id'];

// Fetch categories (for Add Novel, if you ever need the full list)
$catStmt   = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch this author's novels (for Add Chapter & My Works)
$novelStmt = $conn->prepare("SELECT id, title FROM novels WHERE author_id = ?");
$novelStmt->execute([$author_id]);
$myNovels = $novelStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Author Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <script src="https://cdn.ckeditor.com/4.20.2/standard/ckeditor.js"></script>


  <style>
    body {
      padding-top: 110px;
      background: #f5f6fa;
      min-height: 100vh;
    }
    .dashboard-main-container {
      max-width: 900px;
      margin: 0 auto;
    }
    .tab-btn { cursor: pointer; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .form-section {
      padding: 2rem 1.5rem;
      background: #fff;
      border-radius: 1rem;
      margin-top: 2rem;
      box-shadow: 0 2px 16px rgba(0,0,0,0.07);
      border: 1px solid #ececec;
    }
    .tab-btn {
      border-radius: 9999px;
      font-weight: 500;
      padding: 0.5rem 2rem;
      font-size: 1.1rem;
      border: 2px solid #ff7e5f;
      background: #fff;
      color: #ff7e5f;
      transition: all 0.2s;
    }
    .tab-btn.active,
    .tab-btn:focus,
    .tab-btn:hover {
      background: linear-gradient(90deg, #ff7e5f, #feb47b);
      color: #fff;
      border-color: #feb47b;
    }
    .btn-gradient {
      background: linear-gradient(90deg, #ff7e5f, #feb47b);
      color: #fff;
      border: none;
      font-weight: 600;
      border-radius: 9999px;
      padding: 0.5rem 2rem;
      transition: background 0.2s;
    }
    .btn-gradient:hover,
    .btn-gradient:focus {
      background: linear-gradient(90deg, #feb47b, #ff7e5f);
      color: #fff;
    }
    .list-group-item-action.cat-result {
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="dashboard-main-container">
    <h2 class="mb-4 text-center">Author Dashboard</h2>

    <!-- Tab Buttons -->
    <div class="d-flex justify-content-center gap-3">
      <div class="btn btn-outline-primary tab-btn active" data-target="tab-add-novel">Add Novel</div>
      <div class="btn btn-outline-primary tab-btn" data-target="tab-add-chapter">Add Chapter</div>
      <div class="btn btn-outline-primary tab-btn" data-target="tab-my-works">My Works</div>
    </div>

    <!-- 1) ADD NOVEL -->
    <div id="tab-add-novel" class="tab-content form-section active">
      <h3>Add New Novel</h3>
      <form action="process_add_novel.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="4" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Cover Image</label>
          <input type="file" name="cover_image" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
          <label class="form-label">Novel Type</label>
          <select name="type" class="form-select" required>
            <option value="original">Original</option>
            <option value="translated">Translated</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select" required>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
            <option value="hiatus">Hiatus</option>
            <option value="dropped">Dropped</option>
          </select>
        </div>

        <!-- Live‑search & badge selection for categories -->
        <div class="mb-3 position-relative">
          <label class="form-label">Categories</label>
          <div id="selected-categories" class="mb-2"></div>
          <input
            type="text"
            id="category-search"
            class="form-control"
            placeholder="Search for a category..."
          >
          <div
            id="category-results"
            class="list-group position-absolute w-50"
            style="z-index:10;"
          ></div>
          <input type="hidden" name="categories_json" id="categories_json">
          <small class="form-text text-muted">
            You can select multiple categories. Duplicates are ignored.
          </small>
        </div>

        <button type="submit" class="btn btn-gradient">Add Novel</button>
      </form>
    </div>

    <!-- 2) ADD CHAPTER -->
    <div id="tab-add-chapter" class="tab-content form-section">
      <h3>Add Chapter to Novel</h3>
      <form action="process_add_chapter.php" method="POST">
        <div class="mb-3">
          <label class="form-label">Select Your Novel</label>
          <select name="novel_id" id="novel_id" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach($myNovels as $n): ?>
              <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Chapter Title</label>
          <input type="text" name="chapter_title" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Chapter Number</label>
          <input type="number" name="chapter_number" id="chapter_number" value="1" class="form-control" min="1" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Content</label>
          <textarea id="chapter-content" name="content" class="form-control" rows="6" required></textarea>
        </div>

        <button type="submit" class="btn btn-gradient">Add Chapter</button>
      </form>
    </div>

<!-- 3) MY WORKS -->
<div id="tab-my-works" class="tab-content form-section">
  <h3>My Works</h3>
  <?php if (empty($myNovels)): ?>
    <div class="alert alert-info">You have not added any novels yet.</div>
  <?php else: ?>
    <div class="list-group">
      <?php foreach($myNovels as $n): ?>
        <div class="list-group-item d-flex justify-content-between align-items-center"
             data-novel-id="<?= $n['id'] ?>">
          <strong><?= htmlspecialchars($n['title']) ?></strong>
          <div>
            <a href="edit_novel.php?id=<?= $n['id'] ?>"
               class="btn btn-sm btn-outline-secondary me-2">Edit</a>
            <a href="delete_novel.php?id=<?= $n['id'] ?>"
               class="btn btn-sm btn-outline-danger me-2"
               onclick="return confirm('Delete this novel and all its chapters?')">Delete</a>
            <button class="btn btn-sm btn-outline-primary toggle-chapters">
              <span class="arrow">▼</span>
            </button>
          </div>
        </div>
        <div class="chapter-list-container mb-3" style="display:none;">
          <!-- chapters will go here -->
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Scripts -->
  <script>
    // Initialize CKEditor for Add Chapter content
    CKEDITOR.replace('chapter-content', {
      height: 300,
      language: 'en',
      toolbar: [
        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
        { name: 'paragraph',   items: ['NumberedList', 'BulletedList', 'Blockquote'] },
        { name: 'insert',      items: ['Link', 'Unlink', 'Image'] },
        { name: 'styles',      items: ['Format'] },
        { name: 'tools',       items: ['Maximize'] }
      ],
      removePlugins: 'elementspath',
      resize_enabled: false
    });

    // Add event listener for novel selection
    document.getElementById('novel_id').addEventListener('change', async function() {
      const novelId = this.value;
      if (novelId) {
        try {
          const response = await fetch(`get_last_chapter.php?novel_id=${novelId}`);
          const data = await response.json();
          if (data.last_chapter !== null) {
            document.getElementById('chapter_number').value = data.last_chapter + 1;
          } else {
            document.getElementById('chapter_number').value = 1;
          }
        } catch (error) {
          console.error('Error fetching last chapter:', error);
        }
      }
    });
  </script>

  <script>
    // Tab switching logic
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn, .tab-content').forEach(el => {
          el.classList.remove('active');
        });
        btn.classList.add('active');
        document.getElementById(btn.dataset.target).classList.add('active');
      });
    });

    // Live‑search & badge‑toggle for categories
    let selectedCategories = [];
    const selectedContainer = document.getElementById('selected-categories');
    const hiddenInput       = document.getElementById('categories_json');
    const resultsBox        = document.getElementById('category-results');
    const searchInput       = document.getElementById('category-search');

    function renderSelected() {
      selectedContainer.innerHTML = '';
      selectedCategories.forEach(cat => {
        const span = document.createElement('span');
        span.className = 'badge bg-secondary me-1 mb-1';
        span.innerHTML = `
          ${cat.name}
          <span class="remove-cat" data-id="${cat.id}" style="cursor:pointer;">&times;</span>
        `;
        selectedContainer.append(span);
      });
      hiddenInput.value = JSON.stringify(selectedCategories.map(c => c.id));
    }

    searchInput.addEventListener('input', () => {
      const term = searchInput.value.trim();
      if (!term) {
        resultsBox.innerHTML = '';
        return;
      }
      // AJAX POST
      fetch('author_dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          ajax_category_search: 1,
          term: term,
          'exclude[]': selectedCategories.map(c => c.id)
        })
      })
      .then(res => res.json())
      .then(list => {
        resultsBox.innerHTML = list.map(cat => `
          <button
            type="button"
            class="list-group-item list-group-item-action cat-result"
            data-id="${cat.id}"
            data-name="${cat.name}"
          >${cat.name}</button>
        `).join('');
      });
    });

    document.addEventListener('click', e => {
      // Add category from results
      if (e.target.classList.contains('cat-result')) {
        const id   = e.target.dataset.id;
        const name = e.target.dataset.name;
        if (!selectedCategories.some(c => c.id == id)) {
          selectedCategories.push({id, name});
          renderSelected();
        }
        resultsBox.innerHTML = '';
        searchInput.value     = '';
      }
      // Remove badge
      if (e.target.classList.contains('remove-cat')) {
        const id = e.target.dataset.id;
        selectedCategories = selectedCategories.filter(c => c.id != id);
        renderSelected();
      }
    });

    // Ensure hidden input is set on form submit
    document.querySelector('form[action="process_add_novel.php"]')
      .addEventListener('submit', () => renderSelected());
  </script>
  

  <script>
document.querySelectorAll('.toggle-chapters').forEach(button => {
  button.addEventListener('click', async () => {
    const item      = button.closest('[data-novel-id]');
    const novelId   = item.dataset.novelId;
    const container = item.nextElementSibling;  // the .chapter-list-container
    const arrowSpan = button.querySelector('.arrow');

    // Toggle open/closed
    const opening = container.style.display === 'none' || !container.style.display;
    if (opening) {
      console.log(`Fetching chapters for novel ${novelId}…`);
      try {
        const res = await fetch(`fetch_chapters.php?novel_id=${novelId}`);
        console.log('Response status:', res.status);
        if (!res.ok) throw new Error(res.statusText);

        const chapters = await res.json();
        console.log('Chapters JSON:', chapters);

        if (chapters.length === 0) {
          container.innerHTML = '<div class="text-muted">No chapters yet.</div>';
        } else {
          let html = '<ul class="list-group">';
          chapters.forEach(ch => {
            html += `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Chapter ${ch.chapter_number}: ${ch.title}
                <a href="edit_chapter.php?id=${ch.id}"
                   class="btn btn-sm btn-outline-secondary">Edit</a>
              </li>`;
          });
          html += '</ul>';
          container.innerHTML = html;
        }
        arrowSpan.textContent = '▲';
        container.style.display = 'block';
      } catch (err) {
        console.error('Error loading chapters:', err);
        container.innerHTML = `<div class="text-danger">Error: ${err.message}</div>`;
        container.style.display = 'block';
      }
    } else {
      // closing
      container.style.display = 'none';
      arrowSpan.textContent = '▼';
    }
  });
});
</script>


  <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
