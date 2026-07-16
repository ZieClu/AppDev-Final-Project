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

    $profile_pic = empty($_SESSION['user']['profile_picture']) ? "profilepictures/default.png" : $_SESSION['user']['profile_picture'];

    if (isset($_POST['delete_listing']))
    {
        $book_wishlist_id = $_POST['delete_listing'];

        $del_sql = "DELETE FROM wishlist WHERE book_id = ? AND user_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $book_wishlist_id, $user_id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }

    // Genres list, for the sort/filter panel checkboxes
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

        $sql = "SELECT DISTINCT b.book_id, b.title, b.cover_image, b.price, w.added_at, u.user_id AS seller_id, u.username, u.profile_picture
                FROM wishlist w
                JOIN books b ON w.book_id = b.book_id
                JOIN book_genres bg ON b.book_id = bg.book_id
                JOIN genres g ON bg.genre_id = g.genre_id
                JOIN users u ON b.seller_id = u.user_id
                WHERE w.user_id = ? AND LOWER(g.genre_name) IN ($placeholders)
                ORDER BY w.added_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        $types = "i" . str_repeat('s', count($lowered_genres));
        mysqli_stmt_bind_param($stmt, $types, $user_id, ...$lowered_genres);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    else
    {
        $sql = "SELECT b.book_id, b.title, b.cover_image, b.price, w.added_at, u.user_id AS seller_id, u.username, u.profile_picture
                FROM wishlist w
                JOIN books b ON w.book_id = b.book_id
                JOIN users u ON b.seller_id = u.user_id
                WHERE w.user_id = ?
                ORDER BY w.added_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Wishlist · BookMarked</title>

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
  <nav class="lib-nav" aria-label="Wishlist navigation">
    <a href="homeFront.php"><span class="spark">&#10022;</span> Back</a>
    <a href="collections.php"><span class="spark">&#10022;</span> Collections</a>
    <a href="library.php"><span class="spark">&#10022;</span> Library</a>
    <a href="userPurchaseTable.php"><span class="spark">&#10022;</span> Purchase History</a>
  </nav>

  <button type="button" class="lib-sort-toggle" id="sortToggleBtn" aria-expanded="false" aria-controls="sortPanel">
    <span class="caret">&#9660;</span> Sort by
  </button>

  <!-- Genres filter panel, reused from the home page filter styling -->
  <form action="wishlist.php" method="get" class="bm-filters-panel lib-filters-panel" id="sortPanel">
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

<!-- WISHLIST HEADING -->
<div class="lib-header-row">
  <div class="lib-avatar">
    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
  </div>
  <h1 class="lib-title" id="libraryTitle"><span id="libraryUsername"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>&rsquo;s Wishlist <span class="spark">&#10022;</span></h1>
</div>

<!-- WISHLIST CONTENT -->
<main class="lib-content">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="libraryGrid">
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
      <?php while ($row = mysqli_fetch_array($result)):
          $book_id = $row['book_id'];
          $title = $row['title'];
          $cover_image = $row['cover_image'];
          $price = $row['price'];
          $seller_id = $row['seller_id'];
          $seller_name = $row['username'];
          $seller_pic = empty($row['profile_picture']) ? "profilepictures/default.png" : $row['profile_picture'];
      ?>
        <div class="col">
          <a href="bookinfo.php?id=<?php echo urlencode($book_id); ?>" class="text-decoration-none">
            <div class="lib-book-card" data-id="<?php echo (int)$book_id; ?>">
              <div class="lib-cover" style="background-image: url('<?php echo htmlspecialchars($cover_image); ?>'); background-size: cover; background-position: center;"></div>
              <div class="lib-book-title"><?php echo htmlspecialchars($title); ?></div>
            </div>
          </a>
          <div class="book-price">Php <?php echo htmlspecialchars($price); ?></div>
          <div class="book-user">
            <img src="<?php echo htmlspecialchars($seller_pic); ?>" style="width:16px; height:16px; border-radius:50%; object-fit:cover;">
            <a href="sellershop.php?seller_id=<?php echo urlencode($seller_id); ?>"><?php echo htmlspecialchars($seller_name); ?></a>
          </div>
          <form method="post" onsubmit="return confirm('Delete this from your wishlist?');" style="margin-top: 0.5rem;">
            <input type="hidden" name="delete_listing" value="<?php echo (int)$book_id; ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <?php if (!$result || mysqli_num_rows($result) === 0): ?>
    <p class="lib-empty-state" id="libraryEmptyState">Your wishlist is empty.</p>
  <?php endif; ?>

  <button type="button" class="bm-divider" id="loadMoreBtn" aria-label="Load more wishlist items"></button>
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

  document.getElementById('loadMoreBtn').addEventListener('click', function () {
    // TODO: pagination — fetch the next page and append to #libraryGrid
    console.log('Load more clicked — wire up pagination.');
  });
});
</script>
</body>
</html>