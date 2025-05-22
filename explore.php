<?php
require_once 'connexion.php';
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Explore - GLOBAL NOVELS</title>
  <link href="css/bootstrap.min.css" rel="stylesheet" />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css"
    rel="stylesheet"
  />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="explore.css" />
</head>

<body class="d-flex flex-column min-vh-100">

  <main class="py-5 flex-shrink-0">
    <div class="container">
      <h2 class="mb-4 text-center fw-bold">Explore Novels</h2>

      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
          <label for="sort-status" class="form-label me-2 mb-0">Status:</label>
          <select id="sort-status" class="form-select d-inline-block w-auto">
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
            <option value="hiatus">Hiatus</option>
            <option value="dropped">Dropped</option>
          </select>
        </div>

        <div class="d-flex align-items-center">
          <form class="d-flex" id="searchForm">
            <input
              class="form-control me-2"
              type="search"
              id="searchInput"
              placeholder="Search novels..."
              aria-label="Search"
            />
            <button class="btn btn-primary" type="submit">Search</button>
          </form>

          <div class="btn-group ms-3" role="group" aria-label="Sort by time">
            <button
              type="button"
              class="btn btn-outline-secondary"
              id="sortCreated"
              title="Sort by creation time"
            >
              <i class="bi bi-clock-history"></i>
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary"
              id="sortUpdated"
              title="Sort by last update time"
            >
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="row g-4" id="novel-list">
      </div>
    </div>
  </main>

  <footer class="bg-dark text-white py-4 mt-auto">
    <div class="container d-flex justify-content-between align-items-center">
      <p class="mb-0">&copy; 2025 MyWebNovel. All rights reserved.</p>
      <nav>
        <ul class="nav">
          <li class="nav-item"><a href="about.html" class="nav-link text-white">About Us</a></li>
          <li class="nav-item"><a href="about.html" class="nav-link text-white">Terms of Service</a></li>
          <li class="nav-item"><a href="about.html" class="nav-link text-white">Privacy Policy</a></li>
        </ul>
      </nav>
    </div>
  </footer>

<script>
  // Wrap all your setup in one function
  function initExplorePage() {
    const statusSelect = document.getElementById('sort-status');
    const searchForm   = document.getElementById('searchForm');
    const searchInput  = document.getElementById('searchInput');
    const novelList    = document.getElementById('novel-list');
    const btnCreated   = document.getElementById('sortCreated');
    const btnUpdated   = document.getElementById('sortUpdated');

    let sortByTime = 'created_at';

    function updateTimeButtons() {
      btnCreated.classList.toggle('active', sortByTime === 'created_at');
      btnUpdated.classList.toggle('active', sortByTime === 'updated_at');
    }

    async function updateNovels() {
      const status = statusSelect.value;
      const query  = searchInput.value.trim();
      const url    = new URL('get_novels.php', window.location.href);
      url.searchParams.set('status', status);
      url.searchParams.set('sortByTime', sortByTime);
      if (query) url.searchParams.set('query', query);

      try {
        const resp   = await fetch(url);
        const novels = await resp.json();
        novelList.innerHTML = '';

        if (!Array.isArray(novels) || novels.length === 0) {
          novelList.innerHTML = `
            <div class="col-12 text-center">
              <p class="fs-5 text-muted">No results found.</p>
            </div>`;
          return;
        }

        novels.forEach(novel => {
          const imgSrc = novel.cover_image
            ? `uploads/covers/${novel.cover_image}`
            : 'uploads/covers/default.jpg';

          novelList.innerHTML += `
            <div class="col-md-4">
              <div class="card novel-card">
                <img src="${imgSrc}" class="card-img-top" alt="${novel.title}">
                <div class="card-body text-center">
                  <h5 class="card-title">${novel.title}</h5>
                  <p class="card-text">${novel.description}</p>
                  <a href="novel.php?id=${novel.id}" class="btn btn-gradient">Read More</a>
                </div>
              </div>
            </div>`;
        });
      } catch (err) {
        console.error('Error fetching novels:', err);
        novelList.innerHTML = `
          <div class="col-12 text-center">
            <p class="fs-5 text-danger">An error occurred while loading novels.</p>
          </div>`;
      }
    }

    // detach existing listeners to avoid duplicates
    statusSelect.replaceWith(statusSelect.cloneNode(true));
    searchForm.replaceWith(searchForm.cloneNode(true));
    btnCreated.replaceWith(btnCreated.cloneNode(true));
    btnUpdated.replaceWith(btnUpdated.cloneNode(true));

    // re-query after clone
    initExplorePage(); // recursion will break, so better to remove detachment logic
  }

  // Better pattern: on pageshow re-init unconditionally
  function init() {
    const statusSelect = document.getElementById('sort-status');
    const searchForm   = document.getElementById('searchForm');
    const searchInput  = document.getElementById('searchInput');
    const novelList    = document.getElementById('novel-list');
    const btnCreated   = document.getElementById('sortCreated');
    const btnUpdated   = document.getElementById('sortUpdated');

    let sortByTime = 'created_at';

    function updateTimeButtons() {
      btnCreated.classList.toggle('active', sortByTime === 'created_at');
      btnUpdated.classList.toggle('active', sortByTime === 'updated_at');
    }

    async function updateNovels() {
      const status = statusSelect.value;
      const query  = searchInput.value.trim();
      const url    = new URL('get_novels.php', window.location.href);
      url.searchParams.set('status', status);
      url.searchParams.set('sortByTime', sortByTime);
      if (query) url.searchParams.set('query', query);

      const resp = await fetch(url);
      const novels = await resp.json();
      novelList.innerHTML = '';
      if (!novels.length) {
        novelList.innerHTML = `
          <div class="col-12 text-center">
            <p class="fs-5 text-muted">No results found.</p>
          </div>`;
        return;
      }
      novels.forEach(novel => {
        const imgSrc = novel.cover_image
          ? `uploads/covers/${novel.cover_image}`
          : 'uploads/covers/default.jpg';
        novelList.innerHTML += `
          <div class="col-md-4">
            <div class="card novel-card">
              <img src="${imgSrc}" class="card-img-top" alt="${novel.title}">
              <div class="card-body text-center">
                <h5 class="card-title">${novel.title}</h5>
                <p class="card-text">${novel.description}</p>
                <a href="novel.php?id=${novel.id}" class="btn btn-gradient">Read More</a>
              </div>
            </div>
          </div>`;
      });
    }

    // wire up events
    statusSelect.addEventListener('change', updateNovels);
    searchForm.addEventListener('submit', e => {
      e.preventDefault();
      updateNovels();
    });
    btnCreated.addEventListener('click', () => {
      sortByTime = 'created_at';
      updateTimeButtons();
      updateNovels();
    });
    btnUpdated.addEventListener('click', () => {
      sortByTime = 'updated_at';
      updateTimeButtons();
      updateNovels();
    });

    updateTimeButtons();
    updateNovels();
  }

  // Run on fresh load...
  window.addEventListener('DOMContentLoaded', init);

  // ...and also when coming back via browser navigation
  window.addEventListener('pageshow', event => {
    if (event.persisted) {
      init();
    }
  });
</script>


</body>
</html>
