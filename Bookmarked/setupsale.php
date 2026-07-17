<?php 
    session_start();
    require 'autologin.php';

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    $title_value = $_SESSION['title'] ?? "";
    $author_value = $_SESSION['author'] ?? "";
    $synopsis_value = $_SESSION['synopsis'] ?? "";
    $price_value = $_SESSION['price'] ?? "";

    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        if (isset($_POST['genre_count']))
        {
            $_SESSION['genre_count'] = $_POST['genre_count'];
        }
        
        if (isset($_POST['title']))
        {
            $_SESSION['title'] = $_POST['title'];
            $title_value = $_POST['title'];
        }
        if (isset($_POST['author']))
        {
            $_SESSION['author'] = $_POST['author'];
            $author_value = $_POST['author'];
        }
        if (isset($_POST['synopsis']))
        {
            $_SESSION['synopsis'] = $_POST['synopsis'];
            $synopsis_value = $_POST['synopsis'];
        }
        if (isset($_POST['price']))
        {
            $_SESSION['price'] = $_POST['price'];
            $price_value = $_POST['price'];
        }
    }

    $genre_count = $_SESSION['genre_count'] ?? 1;

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
                $bookcovername = "bookcoversLoader/" . $book_id . "." . $cover_extension;

                if (!is_dir('bookcoversLoader'))
                {
                    mkdir('bookcoversLoader', 0777, true);
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
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title id="pageTitle">New Listing · BookMarked</title>

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
    <label class="ss-cover-box" id="coverBox" for="coverInput">
      <span class="ss-plus">&#43;</span>
      <img id="coverPreview" src="" alt="" hidden>
    </label>
    <input type="file" id="coverInput" name="cover_image" form="entryForm" accept="image/*" hidden>
    <label for="coverInput" class="ss-add-cover-link">Add Book Cover</label>
  </div>

  <!-- Entry form -->
  <div class="ss-form-panel">
    <h2 class="ss-heading" id="formHeading"><span class="spark">&#10022;</span> New Listing</h2>
    <hr class="ss-divider">

    <?php if (isset($msg)): ?>
      <p style="font-weight: 600; margin-bottom: 1.25rem; color: <?php echo strpos($msg, 'successfully') !== false ? '#1c4a3d' : '#8f3722'; ?>;"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <form action="setupsale.php" method="post" enctype="multipart/form-data" id="entryForm">
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
            <input
              type="text"
              name="genres[<?php echo $i; ?>]"
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

        <!-- Only shown when editing an existing listing -->
        <span class="ss-delete-link d-none" id="deleteLink">Delete this listing</span>

        <button type="submit" name="add_book" value="1" class="ss-apply-btn">Apply</button>
      </div>
    </form>
  </div>

</main>

<script>
/*
  BACKEND HOOK: this page doubles as "add new listing" and "edit existing
  listing" depending on how it's linked to, e.g.
    setupsale.php                -> add mode
    setupsale.php?bookId=123     -> edit mode, pre-fill the form below

  NOTE: only add-mode has a working backend right now (the INSERT logic
  at the top of this file). Edit/delete for an existing listing still
  needs its own PHP handling before this mode can actually work.
*/
function getEditingBookId() {
  const params = new URLSearchParams(window.location.search);
  return params.get('bookId');
}

document.addEventListener('DOMContentLoaded', function () {
  const bookId = getEditingBookId();
  const heading = document.getElementById('formHeading');
  const pageTitle = document.getElementById('pageTitle');
  const deleteLink = document.getElementById('deleteLink');

  if (bookId) {
    // Edit mode (UI only — see BACKEND HOOK note above)
    heading.innerHTML = '<span class="spark">&#10022;</span> Edit Entry';
    pageTitle.textContent = 'Edit Entry · BookMarked';
    deleteLink.classList.remove('d-none');
  }

  // Cover image preview
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
  // A page reload would otherwise silently clear the selected cover file —
  // browsers never let a file input's selection survive a form resubmit/reload,
  // no matter what PHP does with everything else.
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

  // Delete listing (edit mode only) — not wired to a backend yet
  deleteLink.addEventListener('click', function () {
    console.log(`Delete book ${bookId} — no backend endpoint for this yet.`);
  });

  // Form submits normally to setupsale.php — no JS interception needed,
  // the PHP at the top of this file handles the POST directly.
});
</script>
</body>
</html>