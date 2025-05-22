<?php
session_start();
require_once 'connexion.php'; 
$reader_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:0;
$username = isset($_SESSION['username'])?$_SESSION['username']:0;
include 'header.php';
?>







<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GLOBAL</title>
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="index.css">
</head>

<body>


    <main>
        <section id="hero" class="text-white text-center py-5" style="background-color: #013686">
            <div class="container">
                <h2 class="display-4 fw-bold">Unlock a World of Stories</h2>
                <p class="lead">
                    Immerse yourself in a vast collection of compelling narratives.
                    Read, write, and explore like never before.
                </p>
                <a href="explore.php" class="btn btn-light btn-lg explore-btn">
                    <span class="btn-text">Explore Now</span>
                    <i class="fas fa-search"></i>
                </a>
            </div>
        </section>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"
            crossorigin="anonymous"></script>

        <!-- <section id="popular" class="py-5 bg-dark">
            <div class="container">
                <h2 class="mb-4 text-center fw-bold">Popular Novels</h2>
                
                <div class="row g-4"> -->

<?php
//include('connexion.php');
//$stmt = $conn -> prepare( "SELECT * FROM novels LIMIT 9");
//$stmt -> execute();
//$i = 0 ;
//while ($novel = $stmt->fetch()) {
//    $id = $novel['id'];
//    $title = $novel['title'];
//    $description = $novel['description'];
//    $cover = $novel['cover_image'];
//
//    echo ' <div class="col-md-4">
//        <div class="card novel-card">
//            <img src="img/$cover" class="card-img-top heith-img" alt="Novel 1" />
//            <div class="card-body text-center">
//                <h5 class="card-title">$title</h5>
//                <p class="card-text">
//                $description
//                </p>
//                <a href="novels/novel.html?id=$id" class="btn btn-gradient">Read More</a>
//                </div>';
//            
//    $i++;
    // if ($i % 3 == 0 && $i != 9) {
    //     echo '</div><br /><div class="row g-4">';
    // }
    // if ($i == 9) {
    //     echo '</div>';
   // }
// }
?>

            <!-- </div>
        </section> -->


<section id="popular" class="py-5 bg-dark">
    <div class="container">
        <h2 class="mb-4 text-center fw-bold text-white">Popular Novels</h2>
        <div class="row g-4" id="popular-novels"></div>
    </div>
</section>


        <section id="categories" class="bg-light py-5">
            <div class="container">
                <h2 class="mb-4">Categories</h2>
                <div class="d-flex flex-wrap gap-3">
                    <?php
                    // Fetch categories from database
                    $stmt = $conn->prepare("SELECT id, name, slug FROM categories ORDER BY name ASC");
                    $stmt->execute();
                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($categories as $category) {
                        echo '<a href="categorie.php?slug=' . htmlspecialchars($category['slug']) . '" class="btn btn-outline-primary">' . 
                             htmlspecialchars($category['name']) . '</a>';
                    }
                    ?>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-dark text-white py-4">
        <div class="container d-flex justify-content-between align-items-center">
            <p class="mb-0">&copy; 2025 MyWebNovel. All rights reserved.</p>
            <nav>
                <ul class="nav">
                    <li class="nav-item">
                        <a href="about.html" class="nav-link text-white">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a href="about.html" class="nav-link text-white">Terms of Service</a>
                    </li>
                    <li class="nav-item">
                        <a href="about.html" class="nav-link text-white">Privacy Policy</a>
                    </li>
                </ul>
            </nav>
        </div>
    </footer>

    <script src="/js/bootstrap.bundle.min.js"></script>

    <SCRIPT>
        function loadPopularNovels() {
    fetch('get_novels1.php?status=popular')
        .then(res => res.json())
        .then(novels => {
            const container = document.getElementById("popular-novels");
            container.innerHTML = '';
            novels.forEach(novel => {
                container.innerHTML += `
                    <div class="col-md-4">
                        <div class="card novel-card">
                            <img src="uploads/covers/${novel.img}" class="card-img-top" alt="${novel.title}" />
                            <div class="card-body text-center">
                                <h5 class="card-title">${novel.title}</h5>
                                <p class="card-text">${novel.desc}</p>
                                <a href="${novel.link}" class="btn btn-gradient">Read More</a>
                            </div>
                        </div>
                    </div>
                `;
            });
        })
        .catch(error => console.error("Error loading popular novels:", error));
}

document.addEventListener("DOMContentLoaded", loadPopularNovels);

    </SCRIPT>
</body>

</html>







