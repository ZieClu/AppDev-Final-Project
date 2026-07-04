<?php
    session_start();

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
            $insert_sql = "INSERT INTO purchases (book_id, buyer_id, seller_id, price_paid)
                        VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "iiid", $book_id, $buyer_id, $seller_id, $price);
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

    if (isset($_POST['submitReview']))
    {
        $rating = intval($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? "");

        if ($already_reviewed)
        {
            $review_msg = "You've already reviewed this book.";
        }
        elseif ($rating < 1 || $rating > 5)
        {
            $review_msg = "Please select a rating from 1 to 5 stars.";
        }
        else
        {
            $insert_review_sql = "INSERT INTO reviews (purchase_id, rating, review_text) VALUES (?, ?, ?)";
            $insert_review_stmt = mysqli_prepare($conn, $insert_review_sql);
            mysqli_stmt_bind_param($insert_review_stmt, "iis", $purchase_id, $rating, $review_text);

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
?>

<style>
    .book-page {
        display: flex;
        gap: 30px;
    }
    .book-left {
        flex: 0 0 200px;
        text-align: center;
    }
    .book-right {
        flex: 1;
    }
    .seller-info {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .seller-pic {
        width: 24px;
        height: 24px;
        border-radius: 50%;
    }
    .review-card {
        display: flex;
        gap: 12px;
        margin-bottom: 15px;
        align-items: flex-start;
    }
    .reviewer-pic {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }
    .review-rating {
        color: #d4a017;
        font-weight: bold;
    }
</style>

<a href="home.php"> BookMarked </a>

<form action="searchresults.php" method="get">
    <input type="text" name="search" placeholder="Search books">
    <input type="submit" name="submitSearch" value="Search">
</form>


<form action="searchresults.php" method="get">
     <details class="genre-dropdown">
        <summary>Filter</summary>
        <div class="genre-options">
            <?php
                include 'connection.php';

                $genre_sql = "SELECT genre_name FROM genres ORDER BY genre_name ASC";
                $genre_result = mysqli_query($conn, $genre_sql);

                while ($genre_row = mysqli_fetch_array($genre_result))
                {
                    $genre_name = $genre_row['genre_name'];
                    echo "<label>";
                    echo "<input type='checkbox' name='genres[]' value='" . htmlspecialchars($genre_name) . "'>";
                    echo htmlspecialchars($genre_name);
                    echo "</label><br>";
                }
            ?>
        </div>
    </details>
    <input type="submit" name="submitSearch" value="Apply">
</form>

<div class="book-page">

     <div class="book-left">
        <img src="<?php echo htmlspecialchars($cover_image); ?>" width="180px">
        <p>Php <?php echo htmlspecialchars($price); ?></p>

        <p>
            <?php if ($review_count > 0): ?>
                <strong><?php echo number_format($avg_rating, 1); ?> / 5</strong>
                (<?php echo $review_count; ?> review<?php echo $review_count == 1 ? "" : "s"; ?>)
            <?php else: ?>
                <strong>0 / 5</strong>
                (0 reviews)
            <?php endif; ?>
        </p>

        <?php if ($purchased): ?>
            <p><strong>Book Purchased.</strong></p>
            <a href="library.php">View Library</a>

        <?php elseif ($is_seller): ?>
            <p><strong>This is your Listing.</strong></p>
            <a href="inventory.php">View Listings</a>

        <?php else: ?>
            <form method="post" onsubmit="return confirm('Buy this book for Php <?php echo htmlspecialchars($price); ?>?');">
                <input type="submit" name="buy_book" value="Buy Now">
            </form>

            <form method="post">
                <input type="submit" name="add_wishlist" value="Add to Wishlist">
            </form>

            <?php if (isset($wishlist_msg)): ?>
                <p><strong><?php echo htmlspecialchars($wishlist_msg); ?></strong></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="book-right">
        <h2><?php echo htmlspecialchars($title); ?></h2>
        <hr>
        <p style="font-weight: bold;"><?php echo htmlspecialchars($author); ?></p>
        <p><?php echo htmlspecialchars($synopsis); ?></p>
        <hr>
        <p style="font-weight: bold;">Genres</p>
        <p>
            <?php
                foreach ($genres as $genre_name)
                {
                    echo htmlspecialchars($genre_name) . " ";
                }
            ?>
        </p>
        <hr>

        <div class="seller-info">
            <img class="seller-pic" src="<?php echo htmlspecialchars($seller_pic); ?>">
            <span><?php echo htmlspecialchars($seller_name); ?></span>
        </div>
        <hr>

        <?php if ($purchased && !$already_reviewed): ?>
            <h3>Write a review</h3>
            <form action="bookinfo.php?id=<?php echo urlencode($book_id); ?>" method="post">
                <label><input type="radio" name="rating" value="5" required> 5</label>
                <label><input type="radio" name="rating" value="4"> 4</label>
                <label><input type="radio" name="rating" value="3"> 3</label>
                <label><input type="radio" name="rating" value="2"> 2</label>
                <label><input type="radio" name="rating" value="1"> 1</label>
                <br>
                <textarea name="review_text" rows="4" cols="50" placeholder="Write your review..."></textarea>
                <br>
                <input type="submit" name="submitReview" value="Submit">
            </form>
        <?php elseif ($already_reviewed && !empty($show_edit_form)): ?>
            <h3>Edit Your Review</h3>
            <form action="bookinfo.php?id=<?php echo urlencode($book_id); ?>" method="post">
                <label><input type="radio" name="rating" value="5" <?php echo $own_review['rating'] == 5 ? "checked" : ""; ?>> 5</label>
                <label><input type="radio" name="rating" value="4" <?php echo $own_review['rating'] == 4 ? "checked" : ""; ?>> 4</label>
                <label><input type="radio" name="rating" value="3" <?php echo $own_review['rating'] == 3 ? "checked" : ""; ?>> 3</label>
                <label><input type="radio" name="rating" value="2" <?php echo $own_review['rating'] == 2 ? "checked" : ""; ?>> 2</label>
                <label><input type="radio" name="rating" value="1" <?php echo $own_review['rating'] == 1 ? "checked" : ""; ?>> 1</label>
                <br>
                <textarea name="review_text" rows="4" cols="50"><?php echo htmlspecialchars($own_review['review_text']); ?></textarea>
                <br>
                <input type="submit" name="edit_review" value="Save Changes">
            </form>
        <?php elseif ($already_reviewed): ?>
            <h3>Your Review</h3>
            <div class="review-rating"><?php echo htmlspecialchars($own_review['rating']); ?> / 5</div>
            <p><?php echo htmlspecialchars($own_review['review_text']); ?></p>

            <form action="bookinfo.php?id=<?php echo urlencode($book_id); ?>" method="post" style="display:inline;">
                <input type="submit" name="show_edit_form" value="Edit">
            </form>
            <form action="bookinfo.php?id=<?php echo urlencode($book_id); ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete your review?');">
                <input type="submit" name="delete_review" value="Delete">
            </form>
        <?php endif; ?>

        <?php if (isset($review_msg)): ?>
            <p><strong><?php echo htmlspecialchars($review_msg); ?></strong></p>
        <?php endif; ?>

        <hr>
        <h4>Reviews</h4>

        <?php
            if (mysqli_num_rows($reviews_result) > 0)
            {
                while ($rev = mysqli_fetch_array($reviews_result))
                {
                    $rev_pic = empty($rev['profile_picture']) ? "profilepictures/default.png" : $rev['profile_picture'];

                    echo "<div class='review-card'>";
                    echo "<img class='reviewer-pic' src='" . htmlspecialchars($rev_pic) . "'>";
                    echo "<div>";
                    echo "<div class='review-rating'>" . htmlspecialchars($rev['rating']) . " / 5</div>";
                    echo "<div><strong>" . htmlspecialchars($rev['username']) . "</strong></div>";
                    echo "<p>" . htmlspecialchars($rev['review_text']) . "</p>";
                    echo "</div>";
                    echo "</div>";
                }
            }
            else
            {
                echo "<p>No reviews yet.</p>";
            }
        ?>
    </div>

</div>