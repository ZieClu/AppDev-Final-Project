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

<?php
    include 'connection.php';

  if (isset($_GET['search']))
    {
        $search_term = trim($_GET['search']);
        $search_term_normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $search_term);
        $like_term = "%" . $search_term_normalized . "%";

        $sql = "SELECT b.book_id, b.title, b.cover_image, b.price, u.username, u.profile_picture
                FROM books b
                JOIN users u ON b.seller_id = u.user_id
                WHERE b.status = 'available' 
                AND LOWER(REPLACE(REPLACE(b.title, '’', \"'\"), '‘', \"'\")) LIKE ?
                ORDER BY b.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $like_term);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        echo "<p>Search results for: \"" . htmlspecialchars($_GET['search']) . "\"</p>";
    }
    elseif (isset($_GET['genres']))
    {
        $selected_genres = array_map('strtolower', $_GET['genres']);
        $placeholders = implode(',', array_fill(0, count($selected_genres), '?'));

        $sql = "SELECT DISTINCT b.book_id, b.title, b.cover_image, b.price, b.created_at, u.username, u.profile_picture
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
            echo "</div>";
        }
    }
    else
    {
        echo "<p>No results found.</p>";
    }
?>
</div>
