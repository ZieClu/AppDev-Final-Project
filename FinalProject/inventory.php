<?php
    session_start();
    require 'autologin.php';

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    $seller_id = $_SESSION['user']['user_id'];

    if (isset($_POST['delete_book']))
    {
        $book_id_to_delete = $_POST['delete_book'];

        $del_sql = "DELETE FROM books WHERE book_id = ? AND seller_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $book_id_to_delete, $seller_id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }

    $earnings_sql = "SELECT SUM(price_paid) AS total_earned FROM purchases WHERE seller_id = ?";
    $earnings_stmt = mysqli_prepare($conn, $earnings_sql);
    mysqli_stmt_bind_param($earnings_stmt, "i", $seller_id);
    mysqli_stmt_execute($earnings_stmt);
    $earnings_result = mysqli_stmt_get_result($earnings_stmt);
    $earnings_row = mysqli_fetch_array($earnings_result);
    $total_earned = $earnings_row['total_earned'] ?? 0;
    mysqli_stmt_close($earnings_stmt);

    $sql = "SELECT book_id, title, author, cover_image, price FROM books WHERE seller_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $seller_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $profile_pic = empty($_SESSION['user']['profile_picture']) ? "profilepictures/default.png" : $_SESSION['user']['profile_picture'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Listings · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as the rest of the site -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">

<style>
  /* --- Inventory / "User's Store" page — extends bookmarked-style.css tokens --- */

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

  .inv-earnings-pill {
    background: var(--sage-300);
    border-radius: 999px;
    padding: 0.6rem 1.4rem;
    font-weight: 700;
    color: var(--ink);
    font-size: 1.02rem;
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

  /* listing card — built on top of the existing .book-card / .book-cover system */
  .inv-listing-title {
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 0.3rem;
    line-height: 1.25;
  }

  .inv-listing-actions {
    display: flex;
    gap: 1.4rem;
    margin-top: 0.2rem;
  }

  .inv-action-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--teal-900);
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 2px;
    font-size: 0.95rem;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
  }

  .inv-action-link:hover { color: var(--teal-800); }

  .inv-action-link.danger { color: var(--danger); }
  .inv-action-link.danger:hover { color: #8a3623; }

  /* "Add Listing" tile — same footprint as a book cover */
  .inv-add-card {
    aspect-ratio: 3 / 4.3;
    border: 2.5px dashed var(--teal-900);
    border-radius: var(--radius-sm);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    text-align: center;
    color: var(--teal-900);
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 600;
    background: #ffffff22;
    transition: background 0.15s ease, transform 0.15s ease;
    cursor: pointer;
    margin-bottom: 0.75rem;
  }

  .inv-add-card:hover {
    background: #ffffff40;
    transform: translateY(-4px);
  }

  .inv-add-card .plus {
    font-size: 2.4rem;
    line-height: 1;
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

<!-- MAIN: User's Store / My Listings -->
<main class="inv-content">

  <div class="inv-header-row">
    <div class="inv-avatar">
      <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
    </div>
    <h1 class="inv-title" id="storeTitle"><?php echo htmlspecialchars($_SESSION['user']['username']);?>'s Store <span class="spark">&#10022;</span></h1>
  </div>

  <div class="inv-meta-row">
    <span class="inv-earnings-pill" id="earningsPill">Earnings: Php <?php echo htmlspecialchars(number_format($total_earned, 2)); ?></span>
    <a href="userTransactionTable.php" class="inv-link">Transaction History</a>
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

                echo "<div class='inv-listing-actions'>";
                echo "<a href='editbook.php?id=" . urlencode($book_id) . "' class='inv-action-link'>&#9998; Edit</a>";

                echo "<form method='post' onsubmit=\"return confirm('Delete this listing? This cannot be undone.');\" style='display:contents;'>";
                echo "<input type='hidden' name='delete_book' value='" . urlencode($book_id) . "'>";
                echo "<button type='submit' class='inv-action-link danger'>&#128465; Delete</button>";
                echo "</form>";
                echo "</div>";

                echo "</div>";
                echo "</div>";
            }
        }

        echo "<div class='col'>";
        echo "<a href='setupsale.php' class='inv-add-card'>";
        echo "<span class='plus'>+</span>";
        echo "<span>Add Listing</span>";
        echo "</a>";
        echo "</div>";
    ?>
  </div>

  <?php if (mysqli_num_rows($result) === 0): ?>
    <p class="inv-empty-state" id="emptyState">You don&rsquo;t have any listings yet — add your first book above.</p>
  <?php endif; ?>

</main>

</body>
</html>