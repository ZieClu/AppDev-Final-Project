<style>
    .book-selection-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    .book-selection-grid label {
        display: block;
        text-align: center;
        cursor: pointer;
    }

    .book-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    .book-card {
        text-align: center;
    }
</style>

<?php
    session_start();

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
?>

<?php
    $collection_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

    $coll_sql = "SELECT collection_name FROM collections WHERE collection_id = ? AND user_id = ?";
    $coll_stmt = mysqli_prepare($conn, $coll_sql);
    mysqli_stmt_bind_param($coll_stmt, "ii", $collection_id, $user_id);
    mysqli_stmt_execute($coll_stmt);
    $coll_result = mysqli_stmt_get_result($coll_stmt);
    $coll_row = mysqli_fetch_array($coll_result);

    if (!$coll_row)
    {
        header("location: collections.php");
        exit;
    }

    $collection_name = $coll_row['collection_name'];
?>

<?php
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
?>

<h2><?php echo htmlspecialchars($collection_name); ?></h2>
<a href="collections.php">Back to Collections</a>

<form action="insidecollections.php" method="post">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($collection_id); ?>">
    <details class="purchased_books">
    <summary>Add Books</summary>
    <div class="book_add">
        <div class="book-selection-grid">
        <?php 
            $booksP_sql = "SELECT DISTINCT b.book_id, b.cover_image, b.title
                            FROM books b
                            JOIN purchases p ON b.book_id = p.book_id
                            WHERE p.buyer_id = ?
                            ORDER BY b.title ASC";
            $booksP_stmt = mysqli_prepare($conn, $booksP_sql);
            mysqli_stmt_bind_param($booksP_stmt, "i", $user_id);
            mysqli_stmt_execute($booksP_stmt);
            $books_result = mysqli_stmt_get_result($booksP_stmt);

            while ($row = mysqli_fetch_array($books_result))
            {
                $book_id = $row['book_id'];
                $cover_image = $row['cover_image'];
                $title = $row['title'];

                echo "<label>";
                echo "<img src='" . htmlspecialchars($cover_image) . "' width='100px'>";
                echo "<p>" . htmlspecialchars($title) . "</p>";
                echo "<input type='checkbox' name='books[]' value='" . htmlspecialchars($book_id) . "'>";
                echo "</label>";
            }
        ?>
        </div>
    </div>
    <input type="submit" name="add_books" value="Add">
    </details>
</form>

<form action="insidecollections.php" method="get">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($collection_id); ?>">
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
    if (isset($_POST['remove_book']))
    {
        $remove_book_id = intval($_POST['remove_book']);

        $del_sql = "DELETE FROM collection_books WHERE collection_id = ? AND book_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $collection_id, $remove_book_id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }

    if (isset($_GET['genres']))
    {
        $selected_genres = array_map('strtolower', $_GET['genres']);
        $placeholders = implode(',', array_fill(0, count($selected_genres), '?'));

        $sql = "SELECT DISTINCT b.book_id, b.title, b.cover_image
                FROM collection_books cb
                JOIN books b ON cb.book_id = b.book_id
                JOIN book_genres bg ON b.book_id = bg.book_id
                JOIN genres g ON bg.genre_id = g.genre_id
                WHERE cb.collection_id = ? AND LOWER(g.genre_name) IN ($placeholders)
                ORDER BY b.title ASC";
        $stmt = mysqli_prepare($conn, $sql);
        $types = "i" . str_repeat('s', count($selected_genres));
        mysqli_stmt_bind_param($stmt, $types, $collection_id, ...$selected_genres);
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

<div class="book-grid">
<?php
    if ($result && mysqli_num_rows($result) > 0)
    {
        while ($row = mysqli_fetch_array($result))
        {
            $book_id = $row['book_id'];
            $title = $row['title'];
            $cover_image = $row['cover_image'];

            echo "<div class='book-card'>";
            echo "<a href='bookinfo.php?id=" . urlencode($book_id) . "'>";
            echo "<img src='" . htmlspecialchars($cover_image) . "' width='100px'>";
            echo "</a>";

            if (isset($my_ratings[$book_id]))
            {
                echo "<p>" . htmlspecialchars($my_ratings[$book_id]) . " / 5</p>";
            }
            
            echo "<p>" . htmlspecialchars($title) . "</p>";

            echo "<form method='post' onsubmit=\"return confirm('Remove this book from the collection?');\">";
            echo "<input type='hidden' name='remove_book' value='" . urlencode($book_id) . "'>";
            echo "<input type='submit' value='Remove'>";
            echo "</form>";

            echo "</div>";
        }
    }
    else
    {
        echo "<p>No books in this collection yet.</p>";
    }
?>
</div>