<?php
    session_start();

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    $user_id = $_SESSION['user']['user_id'];

    if (isset($_POST['createCollection']))
    {
        $collection_name = trim($_POST['collectionName']);

        if ($collection_name !== "")
        {
            $insert_sql = "INSERT INTO collections (user_id, collection_name) VALUES (?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "is", $user_id, $collection_name);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
        }
    }

    if (isset($_POST['delete_collection']))
    {
        $collection_id = intval($_POST['delete_collection']);

        $del_sql = "DELETE FROM collections WHERE collection_id = ? AND user_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $collection_id, $user_id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }

    $sql = "SELECT collection_id, collection_name FROM collections WHERE user_id = ? ORDER BY created_at ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
?>

<style>
    .collection-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    .collection-card {
        text-align: center;
    }
</style>

<h2>My Collections</h2>
<a href="home.php"> Back to Store </a>
<a href="library.php"> Library </a>
<a href="wishlist.php"> Wishlist</a>

<form action="collections.php" method="get">
    <input type="submit" name="addCollection" value="Add Collection"/>
</form>

<?php if (isset($_GET['addCollection'])): ?>
    <form action="collections.php" method="post">
        <input type="text" name="collectionName" placeholder="Collection Name">
        <input type="submit" name="createCollection" value="Create Collection">
    </form>
<?php endif; ?>

<div class="collection-grid">
<?php
    if (mysqli_num_rows($result) > 0)
    {
        while ($row = mysqli_fetch_array($result))
        {
            $collection_id = $row['collection_id'];
            $collection_name = $row['collection_name'];

            echo "<div class='collection-card'>";
            echo "<a href='insidecollections.php?id=" . urlencode($collection_id) . "'>" . htmlspecialchars($collection_name) . "</a>";

            echo "<form method='post' onsubmit=\"return confirm('Delete this collection?');\">";
            echo "<input type='hidden' name='delete_collection' value='" . urlencode($collection_id) . "'>";
            echo "<input type='submit' value='Delete'>";
            echo "</form>";

            echo "</div>";
        }
    }
    else
    {
        echo "<p>You have no collections yet.</p>";
    }
?>
</div>