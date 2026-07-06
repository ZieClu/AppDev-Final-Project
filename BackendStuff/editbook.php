<?php
    session_start();

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    $seller_id = $_SESSION['user']['user_id'];

    if (isset($_GET['id']))
    {
        $book_id = intval($_GET['id']);

        $check_sql = "SELECT * FROM books WHERE book_id = ? AND seller_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $book_id, $seller_id);
        mysqli_stmt_execute($check_stmt);
        $book_result = mysqli_stmt_get_result($check_stmt);
        $book_row = mysqli_fetch_assoc($book_result);
        mysqli_stmt_close($check_stmt);

        if (!$book_row)
        {
            header("location: inventory.php");
            exit;
        }

        $genre_sql = "SELECT g.genre_name FROM genres g
                       JOIN book_genres bg ON g.genre_id = bg.genre_id
                       WHERE bg.book_id = ?";
        $genre_stmt = mysqli_prepare($conn, $genre_sql);
        mysqli_stmt_bind_param($genre_stmt, "i", $book_id);
        mysqli_stmt_execute($genre_stmt);
        $genre_result = mysqli_stmt_get_result($genre_stmt);

        $existing_genres = [];
        while ($g = mysqli_fetch_assoc($genre_result))
        {
            $existing_genres[] = $g['genre_name'];
        }
        mysqli_stmt_close($genre_stmt);

        $_SESSION['edit_book_id'] = $book_id;
        $_SESSION['edit_title'] = $book_row['title'];
        $_SESSION['edit_author'] = $book_row['author'];
        $_SESSION['edit_synopsis'] = $book_row['synopsis'];
        $_SESSION['edit_price'] = $book_row['price'];
        $_SESSION['edit_genre_count'] = count($existing_genres) > 0 ? count($existing_genres) : 1;
        $_SESSION['edit_genres'] = $existing_genres;
    }

    if (!isset($_SESSION['edit_book_id']))
    {
        header("location: inventory.php");
        exit;
    }

    $book_id = $_SESSION['edit_book_id'];

    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        if (isset($_POST['genre_count']))
        {
            $_SESSION['edit_genre_count'] = $_POST['genre_count'];
        }
        if (isset($_POST['title']))
        {
            $_SESSION['edit_title'] = $_POST['title'];
        }
        if (isset($_POST['author']))
        {
            $_SESSION['edit_author'] = $_POST['author'];
        }
        if (isset($_POST['synopsis']))
        {
            $_SESSION['edit_synopsis'] = $_POST['synopsis'];
        }
        if (isset($_POST['price']))
        {
            $_SESSION['edit_price'] = $_POST['price'];
        }
        if (isset($_POST['genres']))
        {
            $_SESSION['edit_genres'] = $_POST['genres'];
        }
    }

    if (isset($_POST['save_changes']))
    {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $synopsis = trim($_POST['synopsis']);
        $price = $_POST['price'];

        $selected_genres = [];
        if (isset($_POST['genres']))
        {
            foreach ($_POST['genres'] as $genre)
            {
                if (!empty($genre))
                {
                    $selected_genres[] = strtolower(trim($genre));
                }
            }
            $selected_genres = array_unique($selected_genres);
        }

        if ($title === "" || $author === "" || $price === "")
        {
            $msg = "Title, author, and price are required.";
        }
        elseif (!is_numeric($price) || $price <= 0)
        {
            $msg = "Please enter a valid price.";
        }
        elseif (empty($selected_genres))
        {
            $msg = "Please add at least one genre.";
        }
        else
        {
            $sql = "UPDATE books SET title = ?, author = ?, synopsis = ?, price = ? WHERE book_id = ? AND seller_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssdii", $title, $author, $synopsis, $price, $book_id, $seller_id);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($result)
            {
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK)
                {
                    if (!is_dir('bookcovers'))
                    {
                        mkdir('bookcovers', 0777, true);
                    }
                    $cover_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                    $bookcovername = "bookcovers/" . $book_id . "." . $cover_extension;

                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $bookcovername))
                    {
                        $cover_sql = "UPDATE books SET cover_image = ? WHERE book_id = ?";
                        $cover_stmt = mysqli_prepare($conn, $cover_sql);
                        mysqli_stmt_bind_param($cover_stmt, "si", $bookcovername, $book_id);
                        mysqli_stmt_execute($cover_stmt);
                        mysqli_stmt_close($cover_stmt);
                    }
                }

                $del_sql = "DELETE FROM book_genres WHERE book_id = ?";
                $del_stmt = mysqli_prepare($conn, $del_sql);
                mysqli_stmt_bind_param($del_stmt, "i", $book_id);
                mysqli_stmt_execute($del_stmt);
                mysqli_stmt_close($del_stmt);

                $genre_success = true;
                foreach ($selected_genres as $genre_name)
                {
                    $check_sql = "SELECT genre_id FROM genres WHERE genre_name = ?";
                    $check_stmt = mysqli_prepare($conn, $check_sql);
                    mysqli_stmt_bind_param($check_stmt, "s", $genre_name);
                    mysqli_stmt_execute($check_stmt);
                    mysqli_stmt_bind_result($check_stmt, $genre_id);

                    if (mysqli_stmt_fetch($check_stmt))
                    {
                        mysqli_stmt_close($check_stmt);
                    }
                    else
                    {
                        mysqli_stmt_close($check_stmt);
                        $insert_sql = "INSERT INTO genres (genre_name) VALUES (?)";
                        $insert_stmt = mysqli_prepare($conn, $insert_sql);
                        mysqli_stmt_bind_param($insert_stmt, "s", $genre_name);
                        $insert_result = mysqli_stmt_execute($insert_stmt);
                        if ($insert_result)
                        {
                            $genre_id = mysqli_insert_id($conn);
                        }
                        else
                        {
                            $genre_success = false;
                            mysqli_stmt_close($insert_stmt);
                            break;
                        }
                        mysqli_stmt_close($insert_stmt);
                    }

                    $link_sql = "INSERT INTO book_genres (book_id, genre_id) VALUES (?, ?)";
                    $link_stmt = mysqli_prepare($conn, $link_sql);
                    mysqli_stmt_bind_param($link_stmt, "ii", $book_id, $genre_id);
                    mysqli_stmt_execute($link_stmt);
                    mysqli_stmt_close($link_stmt);
                }

                if ($genre_success)
                {
                    unset($_SESSION['edit_book_id']);
                    unset($_SESSION['edit_title']);
                    unset($_SESSION['edit_author']);
                    unset($_SESSION['edit_synopsis']);
                    unset($_SESSION['edit_price']);
                    unset($_SESSION['edit_genre_count']);
                    unset($_SESSION['edit_genres']);

                    header("location: inventory.php");
                    exit;
                }
            }
            else
            {
                $msg = "Error updating book: " . mysqli_error($conn);
            }
        }
    }

    $genre_count = isset($_SESSION['edit_genre_count']) ? $_SESSION['edit_genre_count'] : 1;
    $title_value = isset($_SESSION['edit_title']) ? $_SESSION['edit_title'] : '';
    $author_value = isset($_SESSION['edit_author']) ? $_SESSION['edit_author'] : '';
    $synopsis_value = isset($_SESSION['edit_synopsis']) ? $_SESSION['edit_synopsis'] : '';
    $price_value = isset($_SESSION['edit_price']) ? $_SESSION['edit_price'] : '';
    $genre_values = isset($_SESSION['edit_genres']) ? $_SESSION['edit_genres'] : [];
?>


<h1><?php echo htmlspecialchars($_SESSION['user']['username']);?>'s Listings</h1>

<form action="editbook.php" method="post" enctype="multipart/form-data">
    Title:
    <input type="text" name="title" value="<?php echo htmlspecialchars($title_value); ?>">
    <br>
    Author:
    <input type="text" name="author" value="<?php echo htmlspecialchars($author_value); ?>">
    <br>
    Synopsis:
    <br>
    <textarea name="synopsis" rows="4" cols="50"><?php echo htmlspecialchars($synopsis_value); ?></textarea>
    <br>
    Price (PHP):
    <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($price_value); ?>">
    <br>
    This book has how many genres?
    <input type="number" name="genre_count" min="1" max="10" value="<?php echo $genre_count; ?>">
    <input type="submit" name="confirmcount" value="Confirm">
    <br>

    <?php
        for ($i = 0; $i < $genre_count; $i++)
        {
            $value = isset($genre_values[$i]) ? htmlspecialchars($genre_values[$i]) : '';
            echo "<input type='text' name='genres[$i]' value='$value' placeholder='Enter Genre'> <br>";
        }
    ?>

    <br><br>
    Book Cover Image:
    <input type="file" name="cover_image">
    <br><br>

    <input type="submit" name="save_changes" value="Save Changes">

    <?php if (isset($msg)): ?>
        <p><strong><?php echo $msg; ?></strong></p>
    <?php endif; ?>
</form>