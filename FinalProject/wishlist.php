<?php
    session_start();

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    $user_id = $_SESSION['user']['user_id'];
?>

<style>
    .book-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    .book-card {
        text-align: center;
    }
    .seller-info {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .seller-pic {
        width: 24px;
        height: 24px;
        border-radius: 50%;
    }
    .genre-dropdown {
        display: inline-block;
        position: relative;
    }
    .genre-dropdown summary {
        cursor: pointer;
        list-style: none;
    }
    .genre-options {
        border: 1px solid #ccc;
        padding: 10px;
        position: absolute;
        background: white;
        z-index: 10;
    }
</style>

<h2>My Wishlist</h2>
<a href="home.php">  Back to Store </a>
<a href="library.php"> Library </a>
<a href="collections.php"> Collections </a>

<form action="wishlist.php" method="get">
     <details class="genre-dropdown">
        <summary>Filter</summary>
        <div class="genre-options">
            <?php
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

<?php

    if (isset($_POST['delete_listing']))
    {
        $book_wishlist_id = $_POST['delete_listing'];

        $del_sql = "DELETE FROM wishlist WHERE book_id = ? AND user_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $book_wishlist_id, $user_id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }

    if (isset($_GET['genres']))
    {
        $selected_genres = array_map('strtolower', $_GET['genres']);
        $placeholders = implode(',', array_fill(0, count($selected_genres), '?'));

        $sql = "SELECT DISTINCT b.book_id, b.title, b.cover_image, b.price, w.added_at, u.username, u.profile_picture
                FROM wishlist w
                JOIN books b ON w.book_id = b.book_id
                JOIN book_genres bg ON b.book_id = bg.book_id
                JOIN genres g ON bg.genre_id = g.genre_id
                JOIN users u ON b.seller_id = u.user_id
                WHERE w.user_id = ? AND LOWER(g.genre_name) IN ($placeholders)
                ORDER BY w.added_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        $types = "i" . str_repeat('s', count($selected_genres));
        mysqli_stmt_bind_param($stmt, $types, $user_id, ...$selected_genres);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    else
    {
        $sql = "SELECT b.book_id, b.title, b.cover_image, b.price, w.added_at, u.username, u.profile_picture
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

<div class="book-grid">
<?php
    if ($result && mysqli_num_rows($result) > 0)
    {
        while ($row = mysqli_fetch_array($result))
        {
            $book_id = $row['book_id'];
            $title = $row['title'];
            $cover_image = $row['cover_image'];
            $price = $row['price'];
            $seller_name = $row['username'];
            $seller_pic = empty($row['profile_picture']) ? "profilepictures/default.png" : $row['profile_picture'];

            echo "<div class='book-card'>";
            echo "<a href='bookinfo.php?id=" . urlencode($book_id) . "'>";
            echo "<img src='" . htmlspecialchars($cover_image) . "' width='100px'>";
            echo "</a>";
            echo "<p>" . htmlspecialchars($title) . "</p>";
            echo "<p>Php " . htmlspecialchars($price) . "</p>";
            echo "<div class='seller-info'>";
            echo "<img class='seller-pic' src='" . htmlspecialchars($seller_pic) . "'>";
            echo "<span>" . htmlspecialchars($seller_name) . "</span>";
            echo "</div>";

            echo "<form method='post' onsubmit=\"return confirm('Delete this from the wishlist?');\">";
            echo "<input type='hidden' name='delete_listing' value='" . urlencode($book_id) . "'>";
            echo "<input type='submit' value='Delete'>";
            echo "</form>";

            echo "</div>"; 
        }
    }
    else
    {
        echo "<p>Your wishlist is empty.</p>";
    }
?>
</div>