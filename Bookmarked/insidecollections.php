<?php
    session_start();
    require 'autologin.php';

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    $user_id = $_SESSION['user']['user_id'];

    $ratings_sql = "SELECT p.book_id, r.rating
                     FROM reviews r
                     JOIN purchases p ON r.purchase_id = p.purchase_id
                     WHERE p.buyer_id = ?";
    $ratings_stmt = mysqli_prepare($conn, $ratings_sql);
    mysqli_stmt_bind_param($ratings_stmt, "i", $user_id);
    mysqli_stmt_execute($ratings_stmt);
    $ratings_result = mysqli_stmt_get_result($ratings_stmt);

    $my_ratings = [];
    while ($r = mysqli_fetch_assoc($ratings_result))
    {
        $my_ratings[$r['book_id']] = $r['rating'];
    }
    mysqli_stmt_close($ratings_stmt);

    $collection_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

    $coll_sql = "SELECT collection_name FROM collections WHERE collection_id = ? AND user_id = ?";
    $coll_stmt = mysqli_prepare($conn, $coll_sql);
    mysqli_stmt_bind_param($coll_stmt, "ii", $collection_id, $user_id);
    mysqli_stmt_execute($coll_stmt);
    $coll_result = mysqli_stmt_get_result($coll_stmt);
    $coll_row = mysqli_fetch_array($coll_result);
    mysqli_stmt_close($coll_stmt);

    if (!$coll_row)
    {
        header("location: collections.php");
        exit;
    }

    $collection_name = $coll_row['collection_name'];

    if (isset($_POST['add_books']) && !empty($_POST['books']))
    {
        $insert_sql = "INSERT IGNORE INTO collection_books (collection_id, book_id) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);

        foreach ($_POST['books'] as $book_id)
        {
            $book_id = intval($book_id);
            mysqli_stmt_bind_param($insert_stmt, "ii", $collection_id, $book_id);
            mysqli_stmt_execute($insert_stmt);
        }

        mysqli_stmt_close($insert_stmt);
    }

    if (isset($_POST['remove_book']))
    {
        $remove_book_id = intval($_POST['remove_book']);

        $del_sql = "DELETE FROM collection_books WHERE collection_id = ? AND book_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $collection_id, $remove_book_id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }

    // Purchased books available to add to this collection
    $purchased_books = [];
    $booksP_sql = "SELECT DISTINCT b.book_id, b.cover_image, b.title
                    FROM books b
                    JOIN purchases p ON b.book_id = p.book_id
                    WHERE p.buyer_id = ?
                    ORDER BY b.title ASC";
    $booksP_stmt = mysqli_prepare($conn, $booksP_sql);
    mysqli_stmt_bind_param($booksP_stmt, "i", $user_id);
    mysqli_stmt_execute($booksP_stmt);
    $books_result = mysqli_stmt_get_result($booksP_stmt);
    while ($row = mysqli_fetch_assoc($books_result))
    {
        $purchased_books[] = $row;
    }
    mysqli_stmt_close($booksP_stmt);

    $selected_genres = isset($_GET['genres']) ? $_GET['genres'] : [];
    if (!is_array($selected_genres)) {
        $selected_genres = [];
    }
    $all_genres = [];
    $genre_sql = "SELECT genre_name FROM genres ORDER BY genre_name ASC";
    $genre_result = mysqli_query($conn, $genre_sql);
    while ($genre_row = mysqli_fetch_assoc($genre_result))
    {
        $all_genres[] = $genre_row['genre_name'];
    }

    if (!empty($selected_genres))
    {
        $lowered_genres = array_map('strtolower', $selected_genres);
        $placeholders = implode(',', array_fill(0, count($lowered_genres), '?'));

        $sql = "SELECT DISTINCT b.book_id, b.title, b.cover_image
                FROM collection_books cb
                JOIN books b ON cb.book_id = b.book_id
                JOIN book_genres bg ON b.book_id = bg.book_id
                JOIN genres g ON bg.genre_id = g.genre_id
                WHERE cb.collection_id = ? AND LOWER(g.genre_name) IN ($placeholders)
                ORDER BY b.title ASC";
        $stmt = mysqli_prepare($conn, $sql);
        $types = "i" . str_repeat('s', count($lowered_genres));
        mysqli_stmt_bind_param($stmt, $types, $collection_id, ...$lowered_genres);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    else
    {
        $sql = "SELECT b.book_id, b.title, b.cover_image
                FROM collection_books cb
                JOIN books b ON cb.book_id = b.book_id
                WHERE cb.collection_id = ?
                ORDER BY b.title ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $collection_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($collection_name); ?> · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as the rest of the app -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">
</head>
<body class="lib-scroll">

<!-- HEADER -->
<header class="bm-header">
  <a href="homeFront.php" class="bm-wordmark" id="homeLogoLink">🕮 BookMarked<span class="dot">.</span></a>
</header>

<!-- SECONDARY NAV TOOLBAR -->
<div class="lib-toolbar">
  <nav class="lib-nav" aria-label="Collection navigation">
    <a href="collections.php"><span class="spark">&#10022;</span> Back to Collections</a>
    <a href="library.php"><span class="spark">&#10022;</span> Library</a>
    <a href="wishlist.php"><span class="spark">&#10022;</span> Wishlist</a>
  </nav>

  <button type="button" class="lib-sort-toggle" id="sortToggleBtn" aria-expanded="false" aria-controls="sortPanel">
    <span class="caret">&#9660;</span> Sort by
  </button>

  <!-- Genres filter panel, reused from the home page filter styling -->
  <form action="insidecollections.php" method="get" class="bm-filters-panel lib-filters-panel" id="sortPanel">
    <input type="hidden" name="id" value="<?php echo (int)$collection_id; ?>">
    <h3>Genres</h3>
    <hr>
    <div class="row" id="genreCheckboxList">
      <?php foreach ($all_genres as $g): ?>
        <div class="col-6">
          <label class="bm-genre-check">
            <input
              type="checkbox"
              name="genres[]"
              value="<?php echo htmlspecialchars($g); ?>"
              <?php echo in_array($g, $selected_genres) ? 'checked' : ''; ?>
            >
            <?php echo htmlspecialchars($g); ?>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="bm-filters-panel-foot">
      <button type="button" class="bm-collapse-btn" id="collapseSortBtn" aria-label="Collapse sort options">&#9650;</button>
      <button type="submit" name="submitSearch" value="Apply" class="bm-apply-btn" id="applySortBtn">Apply</button>
    </div>
  </form>
</div>

<!-- COLLECTION HEADING -->
<div class="lib-header-row">
  <h1 class="lib-title" id="libraryTitle"><?php echo htmlspecialchars($collection_name); ?> <span class="spark">&#10022;</span></h1>
</div>

<!-- ADD BOOKS PANEL -->
<div style="padding: 0 2.5rem;">
  <details style="background: var(--tan-400, #d9bb8a); border-radius: var(--radius-lg, 12px); padding: 1rem 1.5rem; margin-bottom: 2rem;">
    <summary style="cursor: pointer; font-family: var(--font-display); font-size: 1.1rem; padding: 0.5rem 0; color: var(--ink, #2b1d12);">Add Books to this Collection</summary>
    <form action="insidecollections.php" method="post">
      <input type="hidden" name="id" value="<?php echo (int)$collection_id; ?>">
      <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3" style="margin-top: 0.5rem;">
        <?php foreach ($purchased_books as $pb): ?>
          <div class="col">
            <label class="lib-book-card" style="cursor: pointer; display: block;">
              <div class="lib-cover" style="background-image: url('<?php echo htmlspecialchars($pb['cover_image']); ?>'); background-size: cover; background-position: center;"></div>
              <div class="lib-book-title" style="font-size: 0.95rem;"><?php echo htmlspecialchars($pb['title']); ?></div>
              <input type="checkbox" name="books[]" value="<?php echo (int)$pb['book_id']; ?>" style="margin-top: 0.4rem;">
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="submit" name="add_books" value="Add" class="bm-apply-btn" style="margin-top: 1rem;">Add Selected</button>
    </form>
  </details>
</div>

<!-- COLLECTION CONTENT -->
<main class="lib-content" style="padding-top: 0;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="libraryGrid">
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
      <?php while ($row = mysqli_fetch_array($result)):
          $book_id = $row['book_id'];
          $title = $row['title'];
          $cover_image = $row['cover_image'];
      ?>
        <div class="col">
          <a href="bookinfo.php?id=<?php echo urlencode($book_id); ?>" class="text-decoration-none">
            <div class="lib-book-card" data-id="<?php echo (int)$book_id; ?>">
              <div class="lib-cover" style="background-image: url('<?php echo htmlspecialchars($cover_image); ?>'); background-size: cover; background-position: center;"></div>
              <div class="lib-book-title"><?php echo htmlspecialchars($title); ?></div>
            </div>
          </a>
          <?php if (isset($my_ratings[$book_id])): ?>
            <p class="review-rating" style="font-size: 0.85rem; margin: 0.4rem 0 0;">Your rating: <?php echo htmlspecialchars($my_ratings[$book_id]); ?> / 5</p>
          <?php endif; ?>
          <form method="post" onsubmit="return confirm('Remove this book from the collection?');" style="margin-top: 0.5rem;">
            <input type="hidden" name="id" value="<?php echo (int)$collection_id; ?>">
            <input type="hidden" name="remove_book" value="<?php echo (int)$book_id; ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
          </form>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <?php if (!$result || mysqli_num_rows($result) === 0): ?>
    <p class="lib-empty-state" id="libraryEmptyState">No books in this collection yet.</p>
  <?php endif; ?>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
// UI-only JS — no fake data, no rendering. PHP above already rendered everything.
document.addEventListener('DOMContentLoaded', function () {
  const sortToggleBtn = document.getElementById('sortToggleBtn');
  const sortPanel = document.getElementById('sortPanel');
  const collapseSortBtn = document.getElementById('collapseSortBtn');

  function toggleSortPanel(forceOpen) {
    const willOpen = forceOpen !== undefined ? forceOpen : !sortPanel.classList.contains('open');
    sortPanel.classList.toggle('open', willOpen);
    sortToggleBtn.setAttribute('aria-expanded', String(willOpen));
  }

  sortToggleBtn.addEventListener('click', () => toggleSortPanel());
  collapseSortBtn.addEventListener('click', () => toggleSortPanel(false));
});
</script>
</body>
</html>