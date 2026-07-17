<?php
    session_start();
    require 'autologin.php';

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    if (!isset($_GET['id']))
    {
        header("location: searchresults.php");
        exit;
    }

    $book_id = intval($_GET['id']);
    $buyer_id = $_SESSION['user']['user_id'];

    $sql = "SELECT b.book_id, b.title, b.author, b.synopsis, b.cover_image, b.price, b.seller_id, u.username, u.profile_picture
            FROM books b
            JOIN users u ON b.seller_id = u.user_id
            WHERE b.book_id = ? AND b.status = 'available'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result);

    if (!$row)
    {
        header("location: searchresults.php");
        exit;
    }

    $title = $row['title'];
    $author = $row['author'];
    $synopsis = $row['synopsis'];
    $cover_image = $row['cover_image'];
    $price = $row['price'];
    $seller_id = $row['seller_id'];
    $seller_name = $row['username'];
    $seller_pic = empty($row['profile_picture']) ? "profilepictures/default.png" : $row['profile_picture'];

    $genre_sql = "SELECT g.genre_name FROM genres g
                   JOIN book_genres bg ON g.genre_id = bg.genre_id
                   WHERE bg.book_id = ?";
    $genre_stmt = mysqli_prepare($conn, $genre_sql);
    mysqli_stmt_bind_param($genre_stmt, "i", $book_id);
    mysqli_stmt_execute($genre_stmt);
    $genre_result = mysqli_stmt_get_result($genre_stmt);

    $genres = [];
    while ($g = mysqli_fetch_assoc($genre_result))
    {
        $genres[] = $g['genre_name'];
    }

    $check_seller_sql = "SELECT seller_id FROM books WHERE book_id = ? AND seller_id = ?";
    $check_seller_stmt = mysqli_prepare($conn, $check_seller_sql);
    mysqli_stmt_bind_param($check_seller_stmt, "ii", $book_id, $_SESSION['user']['user_id']);
    mysqli_stmt_execute($check_seller_stmt);
    $check_seller_result = mysqli_stmt_get_result($check_seller_stmt);
    $is_seller = mysqli_num_rows($check_seller_result) > 0;
    mysqli_stmt_close($check_seller_stmt);

    $check_sql = "SELECT purchase_id FROM purchases WHERE book_id = ? AND buyer_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $book_id, $buyer_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $purchase_row = mysqli_fetch_array($check_result);
    $purchased = $purchase_row !== null;
    $purchase_id = $purchased ? $purchase_row['purchase_id'] : null;
    mysqli_stmt_close($check_stmt);

    if (isset($_POST['buy_book']))
    {
        if (!$purchased)
        {
            $insert_sql = "INSERT INTO purchases (book_id, buyer_id, seller_id, price_paid, book_title, author, cover_image)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "iiidsss", $book_id, $buyer_id, $seller_id, $price, $title, $author, $cover_image);
            mysqli_stmt_execute($insert_stmt);
            $purchase_id = mysqli_insert_id($conn);
            mysqli_stmt_close($insert_stmt);

            $purchased = true;
        }
    }

    if (isset($_POST['add_wishlist']))
    {
        try {
            $insert_sql = "INSERT INTO wishlist (book_id, user_id) VALUES (?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ii", $book_id, $buyer_id);
            mysqli_stmt_execute($insert_stmt);
            $wishlist_msg = "Book added to wishlist!";
            mysqli_stmt_close($insert_stmt);
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $wishlist_msg = "Book already in wishlist!";
            } else {
                $wishlist_msg = "Error adding to wishlist. Please try again.";
            }
        }
    }

    $own_review = null;
    $already_reviewed = false;
    if ($purchased && $purchase_id)
    {
        $rev_check_sql = "SELECT review_id, rating, review_text FROM reviews WHERE purchase_id = ?";
        $rev_check_stmt = mysqli_prepare($conn, $rev_check_sql);
        mysqli_stmt_bind_param($rev_check_stmt, "i", $purchase_id);
        mysqli_stmt_execute($rev_check_stmt);
        $rev_check_result = mysqli_stmt_get_result($rev_check_stmt);
        $own_review = mysqli_fetch_assoc($rev_check_result);
        $already_reviewed = $own_review !== null;
        mysqli_stmt_close($rev_check_stmt);
    }

    // FIXED: Added book_id to INSERT
    if (isset($_POST['submitReview']))
    {
        $rating = intval($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? "");

        if ($already_reviewed)
        {
            $review_msg = "";
        }
        elseif ($rating < 1 || $rating > 5)
        {
            $review_msg = "Please select a rating from 1 to 5 stars.";
        }
        else
        {
            $insert_review_sql = "INSERT INTO reviews (purchase_id, book_id, rating, review_text) VALUES (?, ?, ?, ?)";
            $insert_review_stmt = mysqli_prepare($conn, $insert_review_sql);
            mysqli_stmt_bind_param($insert_review_stmt, "iiis", $purchase_id, $book_id, $rating, $review_text);

            if (mysqli_stmt_execute($insert_review_stmt))
            {
                $review_msg = "Review submitted. Thank you!";
                $already_reviewed = true;
                $own_review = ["rating" => $rating, "review_text" => $review_text];
            }
            else
            {
                $review_msg = "Error submitting review. Please try again.";
            }

            mysqli_stmt_close($insert_review_stmt);
        }
    }

    if (isset($_POST['edit_review']))
    {
        $rating = intval($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? "");

        if ($rating < 1 || $rating > 5)
        {
            $review_msg = "Please select a rating from 1 to 5 stars.";
            $show_edit_form = true;
        }
        else
        {
            $update_review_sql = "UPDATE reviews SET rating = ?, review_text = ? WHERE purchase_id = ?";
            $update_review_stmt = mysqli_prepare($conn, $update_review_sql);
            mysqli_stmt_bind_param($update_review_stmt, "isi", $rating, $review_text, $purchase_id);

            if (mysqli_stmt_execute($update_review_stmt))
            {
                $own_review['rating'] = $rating;
                $own_review['review_text'] = $review_text;
                header("location: bookinfo.php?id=" . urlencode($book_id));
                exit;
            }
            else
            {
                $review_msg = "Error updating review. Please try again.";
                $show_edit_form = true;
            }

            mysqli_stmt_close($update_review_stmt);
        }
    }

    if (isset($_POST['delete_review']))
    {
        $delete_review_sql = "DELETE FROM reviews WHERE purchase_id = ?";
        $delete_review_stmt = mysqli_prepare($conn, $delete_review_sql);
        mysqli_stmt_bind_param($delete_review_stmt, "i", $purchase_id);
        mysqli_stmt_execute($delete_review_stmt);
        mysqli_stmt_close($delete_review_stmt);

        header("location: bookinfo.php?id=" . urlencode($book_id));
        exit;
    }

    if (isset($_POST['show_edit_form']))
    {
        $show_edit_form = true;
    }

    $avg_sql = "SELECT AVG(r.rating) AS avg_rating, COUNT(r.rating) AS review_count
                FROM reviews r
                JOIN purchases p ON r.purchase_id = p.purchase_id
                WHERE p.book_id = ?";
    $avg_stmt = mysqli_prepare($conn, $avg_sql);
    mysqli_stmt_bind_param($avg_stmt, "i", $book_id);
    mysqli_stmt_execute($avg_stmt);
    $avg_result = mysqli_stmt_get_result($avg_stmt);
    $avg_row = mysqli_fetch_array($avg_result);
    $avg_rating = $avg_row['avg_rating'];
    $review_count = $avg_row['review_count'];
    mysqli_stmt_close($avg_stmt);

    $reviews_sql = "SELECT r.rating, r.review_text, r.created_at, u.username, u.profile_picture
                    FROM reviews r
                    JOIN purchases p ON r.purchase_id = p.purchase_id
                    JOIN users u ON p.buyer_id = u.user_id
                    WHERE p.book_id = ?
                    ORDER BY r.created_at DESC";
    $reviews_stmt = mysqli_prepare($conn, $reviews_sql);
    mysqli_stmt_bind_param($reviews_stmt, "i", $book_id);
    mysqli_stmt_execute($reviews_stmt);
    $reviews_result = mysqli_stmt_get_result($reviews_stmt);

    $all_genres = [];
    $all_genre_result = mysqli_query($conn, "SELECT genre_name FROM genres ORDER BY genre_name ASC");
    while ($ag = mysqli_fetch_assoc($all_genre_result))
    {
        $all_genres[] = $ag['genre_name'];
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($title); ?> · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as the rest of the site -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">
</head>
<body>

<!-- HEADER (identical to home.php — clicking the wordmark returns to the listings) -->
<header class="bm-header">
  <a href="homeFront.php" class="bm-wordmark" id="homeLogoLink">🕮 BookMarked<span class="dot">.</span></a>
</header>

<!-- TOOLBAR (identical to home.php so the search/filters stay reachable from a book page) -->
<div class="bm-toolbar">
  <button class="bm-filters-toggle" id="filtersToggleBtn" aria-expanded="false" aria-controls="filtersPanel">
    <span class="caret">&#9660;</span> Filters
  </button>

  <form id="searchForm" class="d-flex flex-grow-1 gap-3" role="search" action="searchresults.php" method="get">
    <input
      type="search"
      class="bm-search-input"
      id="searchInput"
      name="search"
      placeholder="Search by title, author, or username&hellip;"
      aria-label="Search books"
    >
    <button type="submit" name="submitSearch" value="Search" class="bm-search-btn">Search</button>
  </form>

  <!-- Genres dropdown (kept for visual/UX parity with home.php; applying here just redirects to filtered results) -->
  <div class="bm-filters-panel" id="filtersPanel">
    <h3>Genres</h3>
    <hr>
    <form action="searchresults.php" method="get">
      <div class="row" id="genreCheckboxList">
        <?php foreach ($all_genres as $g): ?>
          <div class="col-6">
            <label class="bm-genre-check">
              <input type="checkbox" name="genres[]" value="<?php echo htmlspecialchars($g); ?>">
              <?php echo htmlspecialchars($g); ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="bm-filters-panel-foot">
        <button type="button" class="bm-collapse-btn" id="collapseFiltersBtn" aria-label="Collapse filters">&#9650;</button>
        <button type="submit" name="submitSearch" value="Apply" class="bm-apply-btn" id="applyFiltersBtn">Apply</button>
      </div>
    </form>
  </div>
</div>

<!-- BOOK INFO LAYOUT -->
<div class="bi-layout">

  <!-- Left: cover + purchase actions -->
  <aside class="bi-cover-panel">
    <div class="bi-cover-frame" id="coverFrame">
      <img src="<?php echo htmlspecialchars($cover_image); ?>" alt="<?php echo htmlspecialchars($title); ?> cover">
    </div>

    <div class="bi-price" id="bookPrice">Php <?php echo htmlspecialchars($price); ?></div>

    <div class="bi-rating-display">
      <div class="bi-stars is-readonly" aria-label="<?php echo number_format($review_count > 0 ? $avg_rating : 0, 1); ?> out of 5 stars">
        <?php
            $rounded_rating = $review_count > 0 ? round($avg_rating) : 0;
            for ($i = 1; $i <= 5; $i++):
        ?>
          <span class="bi-star <?php echo $i <= $rounded_rating ? 'is-filled' : ''; ?>"></span>
        <?php endfor; ?>
      </div>
      <span class="bi-rating-count">
        <?php if ($review_count > 0): ?>
          <?php echo number_format($avg_rating, 1); ?> / 5 (<?php echo $review_count; ?> review<?php echo $review_count == 1 ? "" : "s"; ?>)
        <?php else: ?>
          0 / 5 (0 reviews)
        <?php endif; ?>
      </span>
    </div>

    <div class="bi-action-row">
      <?php if ($purchased): ?>
        <div class="bi-status-text">Book Purchased.</div>
        <a href="library.php" class="bi-wishlist-btn bi-status-link">View Library</a>
      <?php elseif ($is_seller): ?>
        <div class="bi-status-text">This is your listing.</div>
        <a href="inventory.php" class="bi-wishlist-btn bi-status-link">View Listings</a>
      <?php else: ?>
            <form method="post" style="display:contents;" onsubmit="return confirm('Buy this book for Php <?php echo htmlspecialchars($price); ?>?');">
                <button type="submit" name="buy_book" class="bi-buy-btn" id="buyNowBtn">Buy Now</button>
            </form>
            <form method="post" style="display:contents;">
                <button type="submit" name="add_wishlist" class="bi-wishlist-btn" id="wishlistBtn">Add to Wishlist</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if (isset($wishlist_msg)): ?>
        <p class="bi-inline-msg" style="flex: 0 0 auto; width: 100%;"><?php echo htmlspecialchars($wishlist_msg); ?></p>
    <?php endif; ?>
  </aside>

  <!-- Right: details -->
  <main class="bi-content-panel">
    <h1 class="bi-title" id="bookTitle"><?php echo htmlspecialchars($title); ?></h1>
    <p class="bi-author" id="bookAuthor">Author: <span><?php echo htmlspecialchars($author); ?></span></p>
    <hr class="bi-divider">

    <h2 class="bi-synopsis-heading">Synopsis:</h2>
    <p class="bi-synopsis-text" id="bookSynopsis"><?php echo htmlspecialchars($synopsis); ?></p>

    <div class="bi-genres-pill">
      <span class="label">Genres:</span>
      <span id="bookGenres">
        <?php foreach ($genres as $genre_name): ?>
          <span class="bi-genre-tag"><?php echo htmlspecialchars(ucfirst($genre_name)); ?></span>
        <?php endforeach; ?>
      </span>
    </div>


    <div class="book-user bi-seller-info">
      <img class="seller-pic" src="<?php echo htmlspecialchars($seller_pic); ?>" width="24" height="24" style="border-radius:50%;">
        Sold by
        <a href="sellerShop.php?seller_id=<?php echo urlencode($seller_id); ?>">
        <?php echo htmlspecialchars($seller_name); ?>
      </a>
    </div>
    <hr class="bi-divider">

    <div class="bi-review-block">
      <?php if ($purchased && !$already_reviewed): ?>
        <form action="bookinfo.php?id=<?php echo urlencode($book_id); ?>" method="post" style="display:contents;">
          <div class="bi-stars" id="starRating" role="radiogroup" aria-label="Rate this book">
            <label class="bi-star" aria-label="1 star"><input type="radio" name="rating" value="1" style="position:absolute; opacity:0;"></label>
            <label class="bi-star" aria-label="2 stars"><input type="radio" name="rating" value="2" style="position:absolute; opacity:0;"></label>
            <label class="bi-star" aria-label="3 stars"><input type="radio" name="rating" value="3" style="position:absolute; opacity:0;"></label>
            <label class="bi-star" aria-label="4 stars"><input type="radio" name="rating" value="4" style="position:absolute; opacity:0;"></label>
            <label class="bi-star" aria-label="5 stars"><input type="radio" name="rating" value="5" required style="position:absolute; opacity:0;"></label>
          </div>

          <div class="bi-reviewer-avatar">
            <img src="<?php echo htmlspecialchars($_SESSION['user']['profile_picture'] ?? 'profilepictures/default.png'); ?>" width="26" height="26" style="border-radius:50%;">
          </div>

          <input type="text" class="bi-review-input" id="reviewInput" name="review_text" placeholder="Write a review">

          <button type="submit" name="submitReview" class="bi-send-btn" aria-label="Submit review">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 11.5L20.5 3.5L13.5 21L10.8 13.7L3 11.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
              <path d="M10.8 13.7L16.5 7.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
          </button>
        </form>

      <?php elseif ($already_reviewed && !empty($show_edit_form)): ?>
        <form action="bookinfo.php?id=<?php echo urlencode($book_id); ?>" method="post" style="display:contents;">
          <div class="bi-stars" id="starRating" role="radiogroup" aria-label="Rate this book">
            <label class="bi-star" aria-label="1 star"><input type="radio" name="rating" value="1" style="position:absolute; opacity:0;" <?php echo $own_review['rating'] == 1 ? "checked" : ""; ?>></label>
            <label class="bi-star" aria-label="2 stars"><input type="radio" name="rating" value="2" style="position:absolute; opacity:0;" <?php echo $own_review['rating'] == 2 ? "checked" : ""; ?>></label>
            <label class="bi-star" aria-label="3 stars"><input type="radio" name="rating" value="3" style="position:absolute; opacity:0;" <?php echo $own_review['rating'] == 3 ? "checked" : ""; ?>></label>
            <label class="bi-star" aria-label="4 stars"><input type="radio" name="rating" value="4" style="position:absolute; opacity:0;" <?php echo $own_review['rating'] == 4 ? "checked" : ""; ?>></label>
            <label class="bi-star" aria-label="5 stars"><input type="radio" name="rating" value="5" style="position:absolute; opacity:0;" <?php echo $own_review['rating'] == 5 ? "checked" : ""; ?>></label>
          </div>

          <div class="bi-reviewer-avatar">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
          </div>

          <input type="text" class="bi-review-input" name="review_text" value="<?php echo htmlspecialchars($own_review['review_text']); ?>">

          <button type="submit" name="edit_review" class="bi-send-btn" aria-label="Save changes">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 11.5L20.5 3.5L13.5 21L10.8 13.7L3 11.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
              <path d="M10.8 13.7L16.5 7.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
          </button>
        </form>

      <?php elseif ($already_reviewed): ?>
        <div class="bi-own-review">
            <h3 class="bi-own-review-heading">Your Review</h3>
            <div style="display:flex; align-items:center; gap:1rem;">
            <div class="bi-reviewer-avatar" style="width:56px; height:56px; overflow:hidden;">
                <img src="<?php echo htmlspecialchars(empty($_SESSION['user']['profile_picture']) ? 'profilepictures/default.png' : $_SESSION['user']['profile_picture']); ?>" width="56" height="56" style="object-fit:cover; border-radius:50%;">
            </div>
            <div class="bi-stars is-readonly">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="bi-star <?php echo $i <= $own_review['rating'] ? 'is-filled' : ''; ?>"></span>
                <?php endfor; ?>
            </div>
            </div>
            <p class="bi-own-review-text" style="margin-left: calc(56px + 1rem);"><?php echo htmlspecialchars($own_review['review_text']); ?></p>

            <div class="bi-review-actions">
            <form action="bookinfo.php?id=<?php echo urlencode($book_id); ?>" method="post">
                <button type="submit" name="show_edit_form" style="margin-left: calc(56px + 1rem);">Edit</button>
            </form>
            <form action="bookinfo.php?id=<?php echo urlencode($book_id); ?>" method="post" onsubmit="return confirm('Delete your review?');">
                <button type="submit" name="delete_review">Delete</button>
            </form>
            </div>
        </div>
    <?php endif; ?>

      <?php if (isset($review_msg)): ?>
        <p class="bi-inline-msg" style="color: var(--ink);"><?php echo htmlspecialchars($review_msg); ?></p>
      <?php endif; ?>
    </div>

    <hr class="bi-divider">

    <h4 class="bi-synopsis-heading">Reviews</h4>
    <?php if (mysqli_num_rows($reviews_result) > 0): ?>
      <?php while ($rev = mysqli_fetch_assoc($reviews_result)):
          $rev_pic = empty($rev['profile_picture']) ? "profilepictures/default.png" : $rev['profile_picture'];
      ?>
        <div class="bi-review-item">
          <div class="book-user">
            <img class="seller-pic" src="<?php echo htmlspecialchars($rev_pic); ?>" width="24" height="24" style="border-radius:50%;">
            <span><?php echo htmlspecialchars($rev['username']); ?></span>
          </div>
          <div class="bi-stars is-readonly">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="bi-star <?php echo $i <= $rev['rating'] ? 'is-filled' : ''; ?>"></span>
            <?php endfor; ?>
          </div>
          <p><?php echo htmlspecialchars($rev['review_text']); ?></p>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No reviews yet.</p>
    <?php endif; ?>
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

  // Star rating hover/click preview for whichever review form is on the page
  // (write or edit — only one renders at a time, so this just no-ops if neither exists)
  document.querySelectorAll('#starRating').forEach(function (group) {
    const labels = Array.from(group.querySelectorAll('.bi-star'));

    function paintUpTo(value) {
      labels.forEach(function (label) {
        const starValue = parseInt(label.querySelector('input').value, 10);
        label.classList.toggle('is-filled', starValue <= value);
      });
    }

    const checkedInput = group.querySelector('input:checked');
    paintUpTo(checkedInput ? parseInt(checkedInput.value, 10) : 0);

    labels.forEach(function (label) {
      const value = parseInt(label.querySelector('input').value, 10);
      label.addEventListener('mouseenter', () => paintUpTo(value));
      label.addEventListener('click', () => paintUpTo(value));
    });

    group.addEventListener('mouseleave', function () {
      const nowChecked = group.querySelector('input:checked');
      paintUpTo(nowChecked ? parseInt(nowChecked.value, 10) : 0);
    });
  });
});
</script>
</body>
</html>