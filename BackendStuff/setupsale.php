<?php 
    session_start();

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    $title_value = $_SESSION['title'] ?? "";
    $author_value = $_SESSION['author'] ?? "";
    $synopsis_value = $_SESSION['synopsis'] ?? "";
    $price_value = $_SESSION['price'] ?? "";
    $genre_count = $_SESSION['genre_count'] ?? 1;

    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        if (isset($_POST['genre_count']))
        {
            $_SESSION['genre_count'] = $_POST['genre_count'];
        }
        
        if (isset($_POST['title']))
        {
            $_SESSION['title'] = $_POST['title'];
        }
        if (isset($_POST['author']))
        {
            $_SESSION['author'] = $_POST['author'];
        }
        if (isset($_POST['synopsis']))
        {
            $_SESSION['synopsis'] = $_POST['synopsis'];
        }
        if (isset($_POST['price']))
        {
            $_SESSION['price'] = $_POST['price'];
        }
    }

    if (isset($_POST['add_book']))
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
            $msg = "Please add at least one genre first.";
        }
        elseif (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK)
        {
            $msg = "Please upload a book cover image.";
        }
        else
        {
            require 'connection.php';

            $seller_id = $_SESSION['user']['user_id'];

            $sql = "INSERT INTO books (seller_id, title, author, synopsis, price, status)
                    VALUES (?, ?, ?, ?, ?, 'available')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isssd", $seller_id, $title, $author, $synopsis, $price);
            $result = mysqli_stmt_execute($stmt);

            if ($result)
            {
                $book_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                $cover_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $bookcovername = "bookcovers/" . $book_id . "." . $cover_extension;

                if (!is_dir('bookcovers'))
                {
                    mkdir('bookcovers', 0777, true);
                }

                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $bookcovername))
                {
                    $sql = "UPDATE books SET cover_image = ? WHERE book_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $bookcovername, $book_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

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
                            $msg = "Error inserting genre: " . mysqli_error($conn);
                            $genre_success = false;
                            mysqli_stmt_close($insert_stmt);
                            break;
                        }
                        mysqli_stmt_close($insert_stmt);
                    }
                    
                    $link_sql = "INSERT INTO book_genres (book_id, genre_id) VALUES (?, ?)";
                    $link_stmt = mysqli_prepare($conn, $link_sql);
                    mysqli_stmt_bind_param($link_stmt, "ii", $book_id, $genre_id);
                    if (!mysqli_stmt_execute($link_stmt))
                    {
                        $msg = "Error linking genre: " . mysqli_error($conn);
                        $genre_success = false;
                        mysqli_stmt_close($link_stmt);
                        break;
                    }
                    mysqli_stmt_close($link_stmt);
                }

                $msg = $genre_success ? "Book listed successfully!" : ($msg ? $msg : "Book added, but there was an error linking some genres.");
                
                if ($genre_success)
                {
                    unset($_SESSION['title']);
                    unset($_SESSION['author']);
                    unset($_SESSION['synopsis']);
                    unset($_SESSION['price']);
                    unset($_SESSION['genre_count']);

                    $_SESSION['flash_msg'] = "Book listed successfully!";
                    header("location: inventory.php");
                    exit;
                }
            }
            else
            {
                $msg = "Error adding book: " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
            }

            mysqli_close($conn);
        }
    }
?>

<form action="setupsale.php" method="post" enctype="multipart/form-data">
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
            echo "<input type='text' name='genres[$i]' placeholder='Enter Genre'> <br>";
        }
    ?>

    <br><br>
    Book Cover Image:
    <input type="file" name="cover_image">
    <br><br>

    <input type="submit" name="add_book" value="Add Book">
    
    <?php if (isset($msg)): ?>
        <p><strong><?php echo $msg; ?></strong></p>
    <?php endif; ?>
</form>
