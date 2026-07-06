<?php session_start(); ?>

<style>
    .page-container {
        display: flex;
        gap: 30px;
    }
    .sidebar {
        width: 150px;
        flex-shrink: 0;
        text-align: center;
    }
    .sidebar-links {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 15px;
    }
    .main-content {
        flex-grow: 1;
    }
    .book-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
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

<?php
    include 'connection.php';
?>

<a href="home.php"> BookMarked </a>

<div class="page-container">

    <div class="sidebar">
        <?php if (isset($_SESSION['user'])): ?>
            <?php
                $username = $_SESSION['user']['username'];
                $profilePicture = empty($_SESSION['user']['profile_picture']) ? "profilepictures/default.png" : $_SESSION['user']['profile_picture'];
            ?>
            <img class="seller-pic" style="width:60px; height:60px;" src="<?php echo htmlspecialchars($profilePicture); ?>">
            <p><?php echo htmlspecialchars($username); ?></p>

            <div class="sidebar-links">
                <a href="profile.php">My Account</a>
                <a href="library.php">My Library</a>
                <a href="inventory.php">My Listings</a>
                <a href="wishlist.php">My Wishlist</a>
            </div>
        <?php else: ?>
            <div class="sidebar-links">
                <a href="login.php">Login</a>
                <a href="register.php">Sign Up</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="main-content">

        <form action="searchresults.php" method="get">
            <input type="text" name="search" placeholder="Search books">
            <input type="submit" name="submitSearch" value="Search">
        </form>

        <form action="searchresults.php" method="get">
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

        <div class="book-grid">
        <?php
            $sql = "SELECT b.book_id, b.title, b.cover_image, b.price, u.username, u.profile_picture
                    FROM books b
                    JOIN users u ON b.seller_id = u.user_id
                    WHERE b.status = 'available'
                    ORDER BY b.created_at DESC";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

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
                echo "</div>";
            }
        ?>
        </div>

    </div>

</div>