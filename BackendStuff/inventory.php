<style>
    .book-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
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

    $sql = "SELECT book_id, title, cover_image, price FROM books WHERE seller_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $seller_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
?>

    <a href="home.php"> BookMarked </a>

    <h1><?php echo htmlspecialchars($_SESSION['user']['username']);?>'s Listings</h1>

    <p><strong>My Earnings: Php <?php echo htmlspecialchars(number_format($total_earned, 2)); ?></strong></p>

<div class="book-grid">
<?php
    if (mysqli_num_rows($result) > 0)
    {
        while ($row = mysqli_fetch_array($result))
        {
            $book_id = $row['book_id'];
            $title = $row['title'];
            $cover_image = $row['cover_image'];
            $price = $row['price'];

            echo "<div class='book-card'>";
            echo "<a href='bookinfo.php?id=" . urlencode($book_id) . "'>";
            echo "<img src='" . htmlspecialchars($cover_image) . "' width='100px'>";
            echo "</a>";
            echo "<p>" . htmlspecialchars($title) . "</p>";
            echo "<p>Php " . htmlspecialchars($price) . "</p>";
            echo "<p><a href='editbook.php?id=" . urlencode($book_id) . "'>Edit</a></p>";

            echo "<form method='post' onsubmit=\"return confirm('Delete this listing? This cannot be undone.');\">";
            echo "<input type='hidden' name='delete_book' value='" . urlencode($book_id) . "'>";
            echo "<input type='submit' value='Delete'>";
            echo "</form>";

            echo "</div>";
        }
    }
    else
    {
        echo "";
    }
?>
</div>

<a href="setupsale.php">Add a Listing</a>
<br>
<a href="home.php">Back to Store</a>
<br>
<a href="userTransactionTable.php">View Sales History</a>