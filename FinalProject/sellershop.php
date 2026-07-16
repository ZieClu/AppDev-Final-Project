<?php
    session_start();
    require 'autologin.php';

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    if (!isset($_GET['seller_id']))
    {
        header("location: homeFront.php");
        exit;
    }

    $seller_id = intval($_GET['seller_id']);

    if ($seller_id === $_SESSION['user']['user_id'])
    {
        header("location: inventory.php");
        exit;
    }

    $seller_sql = "SELECT user_id, username, profile_picture FROM users WHERE user_id = ?";
    $seller_stmt = mysqli_prepare($conn, $seller_sql);
    mysqli_stmt_bind_param($seller_stmt, "i", $seller_id);
    mysqli_stmt_execute($seller_stmt);
    $seller_result = mysqli_stmt_get_result($seller_stmt);
    $seller_row = mysqli_fetch_array($seller_result);
    mysqli_stmt_close($seller_stmt);

    if (!$seller_row)
    {
        header("location: homeFront.php");
        exit;
    }

    $seller_pic = empty($seller_row['profile_picture']) ? "profilepictures/default.png" : $seller_row['profile_picture'];
    $seller_name = $seller_row['username'];

    $sql = "SELECT book_id, title, author, cover_image, price FROM books WHERE seller_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $seller_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($seller_name); ?>'s Store · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as the rest of the site -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">

<style>
  /* --- Seller shop page — same tokens/classes as inventory.php's "My Store" page,
       reused here since this is the same layout in read-only, someone-else's-shop mode --- */

  .inv-content {
    position: relative;
    z-index: 1;
    max-width: 1180px;
    margin: 0 auto;
    padding: 2.75rem 2.5rem 4rem;
  }

  .inv-header-row {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.75rem;
  }

  .inv-avatar {
    width: 116px;
    height: 116px;
    border-radius: 50%;
    background: var(--cream-50);
    border: 4px solid var(--teal-900);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal-900);
    overflow: hidden;
  }

  .inv-title {
    font-family: var(--font-display);
    font-size: 3rem;
    color: var(--teal-900);
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin: 0;
  }

  .inv-title .spark {
    color: var(--teal-800);
    font-size: 2.1rem;
  }

  .inv-meta-row {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
    padding-bottom: 1.1rem;
    border-bottom: 3px solid var(--teal-900);
    margin-bottom: 2.25rem;
  }

  .inv-link {
    color: var(--teal-900);
    font-weight: 600;
    font-size: 1.05rem;
    text-decoration: underline;
    text-underline-offset: 3px;
  }

  .inv-link:hover { color: var(--teal-800); }

  .inv-back-link { margin-left: auto; }

  .inv-listing-title {
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 0.3rem;
    line-height: 1.25;
  }

  .inv-empty-state {
    color: var(--ink-soft);
    font-style: italic;
    padding: 2rem 0;
  }

  @media (max-width: 576px) {
    .inv-content { padding: 2rem 1.25rem 3rem; }
    .inv-title { font-size: 2.1rem; }
    .inv-avatar { width: 88px; height: 88px; }
    .inv-back-link { margin-left: 0; }
  }
</style>
</head>
<body>

<!-- HEADER (shared across the site) -->
<header class="bm-header">
  <a href="homeFront.php" class="bm-wordmark" id="homeLogoLink">🕮 BookMarked<span class="dot">.</span></a>
</header>

<!-- MAIN: someone else's store, read-only -->
<main class="inv-content">

  <div class="inv-header-row">
    <div class="inv-avatar">
      <img src="<?php echo htmlspecialchars($seller_pic); ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
    </div>
    <h1 class="inv-title" id="storeTitle"><?php echo htmlspecialchars($seller_name); ?>'s Store <span class="spark">&#10022;</span></h1>
  </div>

  <div class="inv-meta-row">
    <!-- No earnings pill, no Transaction History — those are private to the store owner -->
    <a href="homeFront.php" class="inv-link inv-back-link">Back</a>
  </div>

  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="listingsGrid">
    <?php
        if (mysqli_num_rows($result) > 0)
        {
            while ($row = mysqli_fetch_array($result))
            {
                $book_id = $row['book_id'];
                $title = $row['title'];
                $author = $row['author'];
                $cover_image = $row['cover_image'];
                $price = $row['price'];

                echo "<div class='col'>";
                echo "<div class='book-card'>";

                echo "<a href='bookinfo.php?id=" . urlencode($book_id) . "'>";
                echo "<div class='book-cover' style=\"background-image: url('" . htmlspecialchars($cover_image) . "');\">";
                echo "<span class='book-cover-title'>" . htmlspecialchars($title) . "</span>";
                echo "<span class='book-cover-author'>" . htmlspecialchars($author) . "</span>";
                echo "</div>";
                echo "</a>";

                echo "<div class='book-price'>Php " . htmlspecialchars($price) . "</div>";
                echo "<div class='inv-listing-title'>" . htmlspecialchars($title) . "</div>";
                // No Edit/Delete actions here — this isn't the logged-in user's own listing

                echo "</div>";
                echo "</div>";
            }
        }
        // No "Add Listing" tile — you can't add to someone else's shop
    ?>
  </div>

  <?php if (mysqli_num_rows($result) === 0): ?>
    <p class="inv-empty-state" id="emptyState"><?php echo htmlspecialchars($seller_name); ?> doesn&rsquo;t have any listings yet.</p>
  <?php endif; ?>

</main>

</body>
</html>