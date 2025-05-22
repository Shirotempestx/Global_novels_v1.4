<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'author') {
    header("Location: login.php");
    exit();
}

$novel_id = $_GET['novel_id'] ?? null;
$author_id = $_SESSION['user_id'];

if (!$novel_id) {
    echo "Invalid novel.";
    exit();
}

$stmt = $conn->prepare("SELECT * FROM novels WHERE id = ? AND author_id = ?");
$stmt->execute([$novel_id, $author_id]);
$novel = $stmt->fetch();

if (!$novel) {
    echo "You do not have access to this novel.";
    exit();
}

$stmt = $conn->prepare("SELECT * FROM chapters WHERE novel_id = ? ORDER BY position ASC, chapter_number ASC");
$stmt->execute([$novel_id]);
$chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Chapters - <?php echo htmlspecialchars($novel['title']); ?></title>
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .chapter-item {
            padding: 12px;
            border: 1px solid #ccc;
            margin-bottom: 8px;
            cursor: grab;
            background: #f8f9fa;
        }
        .chapter-item:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body class="container mt-5">
    <h2>Manage Chapters for "<?php echo htmlspecialchars($novel['title']); ?>"</h2>
    <div id="chapterList">
        <?php foreach ($chapters as $chapter): ?>
            <div class="chapter-item" data-id="<?php echo $chapter['id']; ?>">
                <strong><?php echo htmlspecialchars($chapter['title']); ?></strong>
                <div>
                    <a href="edit_chapter.php?id=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="delete_chapter.php?id=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    <a href="preview_chapter.php?id=<?php echo $chapter['id']; ?>" target="_blank" class="btn btn-sm btn-info">Preview</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="statusMsg" class="mt-3"></div>

    <script>
        const container = document.getElementById('chapterList');
        let dragSrcEl = null;

        container.addEventListener('dragstart', function (e) {
            dragSrcEl = e.target;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.target.outerHTML);
            e.target.classList.add('dragging');
        });

        container.addEventListener('dragover', function (e) {
            e.preventDefault();
        });

        container.addEventListener('drop', function (e) {
            e.preventDefault();
            if (e.target.closest('.chapter-item') && dragSrcEl !== e.target.closest('.chapter-item')) {
                const dropTarget = e.target.closest('.chapter-item');
                container.insertBefore(dragSrcEl, dropTarget.nextSibling);
                updateOrder();
            }
        });

        container.querySelectorAll('.chapter-item').forEach(item => {
            item.setAttribute('draggable', 'true');
        });

        function updateOrder() {
            const order = [];
            container.querySelectorAll('.chapter-item').forEach((item, index) => {
                order.push({
                    id: item.dataset.id,
                    position: index + 1
                });
            });

            fetch('update_chapter_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(order)
            })
            .then(res => res.text())
            .then(response => {
                document.getElementById('statusMsg').innerHTML = `<div class="alert alert-success">${response}</div>`;
            });
        }
    </script>
</body>
</html>
