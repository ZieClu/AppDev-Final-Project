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
        $_SESSION['edit_cover_image'] = $book_row['cover_image'];
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

    if (isset($_POST['delete_listing']))
    {
        $del_sql = "DELETE FROM books WHERE book_id = ? AND seller_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $book_id, $seller_id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);

        unset($_SESSION['edit_book_id']);
        unset($_SESSION['edit_title']);
        unset($_SESSION['edit_author']);
        unset($_SESSION['edit_synopsis']);
        unset($_SESSION['edit_price']);
        unset($_SESSION['edit_cover_image']);
        unset($_SESSION['edit_genre_count']);
        unset($_SESSION['edit_genres']);

        header("location: inventory.php");
        exit;
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
                    if (!is_dir('bookcoversLoader'))
                    {
                        mkdir('bookcoversLoader', 0777, true);
                    }
                    $cover_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                    $bookcovername = "bookcoversLoader/" . $book_id . "." . $cover_extension;

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
                    unset($_SESSION['edit_cover_image']);
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

    $genre_count = $_SESSION['edit_genre_count'] ?? 1;
    $title_value = $_SESSION['edit_title'] ?? '';
    $author_value = $_SESSION['edit_author'] ?? '';
    $synopsis_value = $_SESSION['edit_synopsis'] ?? '';
    $price_value = $_SESSION['edit_price'] ?? '';
    $cover_image_value = $_SESSION['edit_cover_image'] ?? '';
    $genre_values = $_SESSION['edit_genres'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Entry · BookMarked</title>

<!-- Bootstrap 5 (kept for consistency with the rest of the site, not required here) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as the rest of BookMarked -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">
<link rel="stylesheet" href="setupsale-style.css">
</head>
<body>

<!-- TOP BAR -->
<header class="ss-header">
  <a href="inventory.php" class="ss-back" id="backLink">Back</a>
</header>

<!-- MAIN -->
<main class="ss-main">

  <!-- Cover uploader -->
  <div class="ss-cover-panel">
    <label class="ss-cover-box <?php echo $cover_image_value ? 'has-image' : ''; ?>" id="coverBox" for="coverInput">
      <span class="ss-plus">&#43;</span>
      <img id="coverPreview" src="<?php echo htmlspecialchars($cover_image_value); ?>" alt="" <?php echo $cover_image_value ? '' : 'hidden'; ?>>
    </label>
    <input type="file" id="coverInput" name="cover_image" form="entryForm" accept="image/*" hidden>
    <label for="coverInput" class="ss-add-cover-link">Change Book Cover</label>
  </div>

  <!-- Entry form -->
  <div class="ss-form-panel">
    <h2 class="ss-heading" id="formHeading"><span class="spark">&#10022;</span> Edit Entry</h2>
    <hr class="ss-divider">

    <?php if (isset($msg)): ?>
      <p style="font-weight: 600; margin-bottom: 1.25rem; color: #8f3722;"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <form action="editbook.php" method="post" enctype="multipart/form-data" id="entryForm">
      <div class="ss-field">
        <label for="titleInput">Title:</label>
        <input type="text" id="titleInput" name="title" autocomplete="off" value="<?php echo htmlspecialchars($title_value); ?>" required>
      </div>

      <div class="ss-field">
        <label for="authorInput">Author:</label>
        <input type="text" id="authorInput" name="author" autocomplete="off" value="<?php echo htmlspecialchars($author_value); ?>" required>
      </div>

      <div class="ss-field ss-field-synopsis">
        <label for="synopsisInput">Synopsis:</label>
        <textarea id="synopsisInput" name="synopsis" rows="5" placeholder="What's this book about?"><?php echo htmlspecialchars($synopsis_value); ?></textarea>
      </div>

      <div class="ss-field">
        <label for="genreCountInput">Genres:</label>
        <input type="number" id="genreCountInput" name="genre_count" min="1" max="10" value="<?php echo (int)$genre_count; ?>" style="width:100px;">
        <button type="button" id="confirmGenreCountBtn" class="ss-apply-btn" style="padding:0.5rem 1.2rem; font-size:1rem;">Confirm</button>
      </div>

      <div class="ss-field ss-genre-field">
        <div class="ss-genre-grid" id="genreGrid" style="grid-template-columns: 1fr; grid-auto-flow: row; row-gap: 0.75rem;">
          <?php for ($i = 0; $i < $genre_count; $i++): ?>
            <?php $genre_val = isset($genre_values[$i]) ? htmlspecialchars($genre_values[$i]) : ''; ?>
            <input
              type="text"
              name="genres[<?php echo $i; ?>]"
              value="<?php echo $genre_val; ?>"
              placeholder="Enter Genre"
              style="max-width:260px; border:none; border-radius:999px; padding:0.55rem 1.1rem; font-family:var(--font-body); background:#fff; color:var(--ink);"
            >
          <?php endfor; ?>
        </div>
      </div>

      <div class="ss-field-row">
        <div class="ss-price-field">
          <label for="priceInput">Price:</label>
          <input type="number" id="priceInput" name="price" min="0" step="1" value="<?php echo htmlspecialchars($price_value); ?>" placeholder="0" required>
        </div>

        <button type="submit" name="save_changes" value="1" class="ss-apply-btn">Save Changes</button>
      </div>
    </form>
  </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const coverInput = document.getElementById('coverInput');
  const coverBox = document.getElementById('coverBox');
  const coverPreview = document.getElementById('coverPreview');

  coverInput.addEventListener('change', function () {
    const file = coverInput.files && coverInput.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    coverPreview.src = url;
    coverPreview.hidden = false;
    coverBox.classList.add('has-image');
  });

  // Genre count: add/remove text inputs in place, no page reload.
  // A page reload would otherwise silently clear a newly chosen cover file —
  // browsers never let a file input's selection survive a form resubmit/reload.
  const genreCountInput = document.getElementById('genreCountInput');
  const confirmGenreCountBtn = document.getElementById('confirmGenreCountBtn');
  const genreGrid = document.getElementById('genreGrid');

  function genreInputHTML(index) {
    return `<input
      type="text"
      name="genres[${index}]"
      placeholder="Enter Genre"
      style="max-width:260px; border:none; border-radius:999px; padding:0.55rem 1.1rem; font-family:var(--font-body); background:#fff; color:var(--ink);"
    >`;
  }

  confirmGenreCountBtn.addEventListener('click', function () {
    const desired = Math.max(1, Math.min(10, parseInt(genreCountInput.value, 10) || 1));
    const current = genreGrid.querySelectorAll('input[name^="genres"]');

    if (desired > current.length) {
      for (let i = current.length; i < desired; i++) {
        genreGrid.insertAdjacentHTML('beforeend', genreInputHTML(i));
      }
    } else if (desired < current.length) {
      for (let i = current.length - 1; i >= desired; i--) {
        current[i].remove();
      }
    }
  });
});
</script>
</body>
</html>