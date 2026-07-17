<?php
    session_start();
    require 'autologin.php';
    include 'connection.php';

    $cartIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 4H5L7.5 15H18L20.5 7H6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1.3" fill="currentColor"/><circle cx="17" cy="19" r="1.3" fill="currentColor"/></svg>';

    if (isset($_GET['search']))
    {
        $search_term = trim($_GET['search']);

        if ($search_term === "")
        {
            $result = false;
        }
        else
        {
            $search_term_normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $search_term);
            $like_term = "%" . $search_term_normalized . "%";

            $sql = "SELECT b.book_id, b.title, b.author, b.cover_image, b.price, b.seller_id, u.username, u.profile_picture
                    FROM books b
                    JOIN users u ON b.seller_id = u.user_id
                    WHERE b.status = 'available' 
                    AND LOWER(REPLACE(REPLACE(b.title, '’', \"'\"), '‘', \"'\")) LIKE ?
                    ORDER BY b.created_at DESC";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $like_term);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        }
    }
    elseif (isset($_GET['genres']))
    {
        $selected_genres = array_map('strtolower', $_GET['genres']);
        $placeholders = implode(',', array_fill(0, count($selected_genres), '?'));

        $sql = "SELECT DISTINCT b.book_id, b.title, b.author, b.cover_image, b.price, b.seller_id, b.created_at, u.username, u.profile_picture
                FROM books b
                JOIN book_genres bg ON b.book_id = bg.book_id
                JOIN genres g ON bg.genre_id = g.genre_id
                JOIN users u ON b.seller_id = u.user_id
                WHERE b.status = 'available' AND LOWER(g.genre_name) IN ($placeholders)
                ORDER BY b.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        $types = str_repeat('s', count($selected_genres));
        mysqli_stmt_bind_param($stmt, $types, ...$selected_genres);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    else
    {
        $result = false;
    }
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Results · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as login/signup -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">
</head>
<body>

<!-- HEADER -->
<header class="bm-header">
  <a href="homeFront.php" class="bm-wordmark" id="homeLogoLink">🕮 BookMarked<span class="dot">.</span></a>
</header>

<!-- TOOLBAR -->
<div class="bm-toolbar">
  <button class="bm-filters-toggle" id="filtersToggleBtn" aria-expanded="false" aria-controls="filtersPanel">
    <span class="caret">&#9660;</span> Filters
  </button>

  <form id="searchForm" action="searchresults.php" class="d-flex flex-grow-1 gap-3" role="search">
    <input
      type="search"
      class="bm-search-input"
      id="searchInput"
      name="search"
      placeholder="Search by title, author, or username&hellip;"
      aria-label="Search books"
    >
    <button type="submit" class="bm-search-btn">Search</button>
  </form>

  <!-- Genres dropdown -->
  <div class="bm-filters-panel" id="filtersPanel">
    <h3>Genres</h3>
    <hr>
    <form action="searchresults.php" method="get">
        <div class="row" id="genreCheckboxList">
            <?php
                    $genre_sql = "SELECT genre_name FROM genres ORDER BY genre_name ASC";
                    $genre_result = mysqli_query($conn, $genre_sql);

                    while ($genre_row = mysqli_fetch_array($genre_result))
                    {
                    $genre_name = $genre_row['genre_name'];

                        echo "<div class='col-6'>";
                        echo "<label class='bm-genre-check'>";
                        echo "<input type='checkbox' name='genres[]' value='" . htmlspecialchars($genre_name) . "'>";
                        echo htmlspecialchars($genre_name);
                        echo "</label>";
                        echo "</div>";
                    }
                ?>
        </div>
    <div class="bm-filters-panel-foot">
      <button type="button" class="bm-collapse-btn" id="collapseFiltersBtn" aria-label="Collapse filters">&#9650;</button>
      <button type="submit" name = "submitSearch" class="bm-apply-btn" id="applyFiltersBtn">Apply</button>
    </div>
    </form>
  </div>
</div>

<!-- LAYOUT -->
<div class="bm-layout">

  <!-- Sidebar -->
  <aside class="bm-sidebar">
  <?php if (isset($_SESSION['user'])): ?>
    <div class="bm-avatar">
        <?php
          $username = $_SESSION['user']['username'];
          $profilePicture = empty($_SESSION['user']['profile_picture']) ? "profilepictures/default.png" : $_SESSION['user']['profile_picture'];
        ?>
        <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
    </div>
    <div class="bm-username" id="sidebarUsername"><?php echo htmlspecialchars($username); ?></div>
    <div class="bm-sidebar-rule"></div>

    <ul class="bm-nav">
      <li><a href="profile.php"><span class="spark">&#10022;</span> My Account</a></li>
      <li><a href="library.php"><span class="spark">&#10022;</span> My Library</a></li>
      <li><a href="inventory.php"><span class="spark">&#10022;</span> My Store</a></li>
      <li><a href="wishlist.php"><span class="spark">&#10022;</span> My Wishlist</a></li>
    </ul>

  <?php else: ?>
    <ul class="bm-nav">
      <li><a href="login.php"><span class="spark">&#10022;</span> Login </a></li>
      <li><a href="register.php"><span class="spark">&#10022;</span> Register </a></li>
    </ul>
  <?php endif; ?>
</aside>

  <!-- Main content -->
  <main class="bm-content">

    <!-- Applied-filters / search-result heading -->
    <div class="bm-section-pill is-applied" id="appliedHeading">
    <h2><span class="spark">&#10022;</span> Filters Applied:</h2>
    <?php if (isset($_GET['genres']) && count($_GET['genres']) > 0): ?>
      <?php foreach ($_GET['genres'] as $genre): ?>
        <span class="bm-chip" style="font-family: var(--font-display);"><?php echo htmlspecialchars($genre); ?></span>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php if (isset($_GET['search']) && trim($_GET['search']) !== ""): ?>
    <p class="bm-search-note">Search results for: "<?php echo htmlspecialchars($_GET['search']); ?>"</p>
  <?php endif; ?>

    <!-- Book grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="bookGrid">
      <?php
          if ($result && mysqli_num_rows($result) > 0)
          {
              while ($row = mysqli_fetch_array($result))
              {
                  $book_id = $row['book_id'];
                  $title = $row['title'];
                  $author = $row['author'];
                  $cover_image = $row['cover_image'];
                  $price = $row['price'];
                  $seller_name = $row['username'];
                  $seller_pic = empty($row['profile_picture']) ? "profilepictures/default.png" : $row['profile_picture'];
                  $seller_id = $row['seller_id'];

                  echo "<div class='col'>";
                  echo "<div class='book-card'>";

                  echo "<a href='bookinfo.php?id=" . urlencode($book_id) . "'>";
                  echo "<div class='book-cover' style=\"background-image: url('" . htmlspecialchars($cover_image) . "');\">";
                  echo "<span class='book-cover-title'>" . htmlspecialchars($title) . "</span>";
                  echo "<span class='book-cover-author'>" . htmlspecialchars($author) . "</span>";
                  echo "</div>";
                  echo "</a>";

                  echo "<div class='book-price'>Php " . htmlspecialchars($price) . "</div>";

                  echo "<a href='bookinfo.php?id=" . urlencode($book_id) . "' class='book-purchase'>" . $cartIcon . " Purchase</a>";

                  echo "<div class='book-user'>";
                  echo "<img src='" . htmlspecialchars($seller_pic) . "' style='width:16px; height:16px; border-radius:50%; object-fit:cover;'>";
                  echo " <a href='sellerShop.php?seller_id=" . urlencode($seller_id) . "'>" . htmlspecialchars($seller_name) . "</a>";
                  echo "</div>";

                  echo "</div>";
                  echo "</div>";
              }
          }
          else
          {
              echo "<p class='bm-empty-state'>No results found — try another genre or search term.</p>";
          }
      ?>
    </div>

  </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const filtersToggleBtn = document.getElementById('filtersToggleBtn');
  const filtersPanel = document.getElementById('filtersPanel');
  const collapseFiltersBtn = document.getElementById('collapseFiltersBtn');

  function toggleFiltersPanel(forceOpen) {
    const willOpen = forceOpen !== undefined ? forceOpen : !filtersPanel.classList.contains('open');
    filtersPanel.classList.toggle('open', willOpen);
    filtersToggleBtn.setAttribute('aria-expanded', String(willOpen));
  }

  filtersToggleBtn.addEventListener('click', () => toggleFiltersPanel());
  collapseFiltersBtn.addEventListener('click', () => toggleFiltersPanel(false));
});
</script>
</body>
</html>
