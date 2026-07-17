<?php
    session_start();
    require 'autologin.php';

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    include 'connection.php';

    $user_id = $_SESSION['user']['user_id'];

    $profile_pic = empty($_SESSION['user']['profile_picture']) ? "profilepictures/default.png" : $_SESSION['user']['profile_picture'];

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
    mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Collections · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as the rest of the site -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">

<style>
  /* ============================================
     COLLECTIONS PAGE (collections.html)
     Page-specific styles — reuses the shared
     variables + components from bookmarked-style.css
     (.lib-toolbar / .lib-nav for the "Back" bar, etc.)
     ============================================ */

  .col-header-row {
    display: flex;
    align-items: center;
    gap: 1.75rem;
    padding: 2.5rem 2.5rem 2rem;
    position: relative;
    z-index: 1;
  }

  .col-avatar {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: var(--sage-100);
    border: 6px solid var(--teal-800);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal-800);
    flex-shrink: 0;
    box-shadow: 0 12px 22px -10px #2b1d1266;
    overflow: hidden;
  }

  .col-title {
    font-family: var(--font-display);
    font-size: 2.75rem;
    color: var(--teal-900);
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin: 0;
  }

  .col-title .spark {
    color: var(--teal-800);
    font-size: 1.9rem;
  }

  .col-content {
    padding: 2rem 2.5rem 3.5rem;
    position: relative;
    z-index: 1;
    min-height: calc(100vh - 320px);
  }

  .col-content-head {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 2rem;
  }

  .col-add-link {
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 1.2rem;
    color: var(--teal-900);
    text-decoration: underline;
    text-underline-offset: 4px;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: none;
    border: none;
    padding: 0;
    transition: color 0.15s ease, transform 0.15s ease;
  }

  .col-add-link:hover {
    color: var(--teal-800);
    transform: translateY(-1px);
  }

  .col-add-link .plus {
    font-size: 1.35rem;
    font-weight: 700;
    text-decoration: none;
  }

  /* Inline create-collection form, toggled by the Add Collection ghost card */
  .col-create-form {
    display: none;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-bottom: 2rem;
  }
  .col-create-form.open {
    display: flex;
  }
  .col-create-form input[type="text"] {
    border: 1px solid var(--teal-800);
    border-radius: var(--radius-sm);
    padding: 0.5rem 0.9rem;
    font-size: 1rem;
  }

  .col-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.75rem;
  }

  .col-card-wrap {
    position: relative;
    height: 190px;
    }


  .col-card {
    background: linear-gradient(160deg, var(--teal-800) 0%, var(--teal-900) 100%);
    border: none;
    border-radius: var(--radius-lg);
    padding: 1.5rem 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1.1rem;
    box-shadow: 0 14px 28px -12px #2b1d1273;
    cursor: pointer;
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    transform: translateY(0) scale(1);
    transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.25s ease;
    text-decoration: none;
}

  .col-card:hover,
  .col-card:focus-visible {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 24px 38px -12px rgba(43, 29, 18, 0.55);
  }

  .col-card-spark {
    font-size: 1.9rem;
    color: var(--cream-100);
  }

    .col-card-title {
        font-family: var(--font-display);
        font-weight: 500;
        font-size: 1.6rem;
        color: var(--cream-100);
        text-align: center;
        width: 100%;
        height: 4.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        word-break: break-word;
        line-height: 1.15;
    }

  .col-delete-form {
    position: absolute;
    top: 0.6rem;
    right: 0.6rem;
    margin: 0;
  }
  .col-delete-form button {
    background: #ffffff26;
    color: var(--cream-100);
    border: none;
    border-radius: 999px;
    width: 26px;
    height: 26px;
    line-height: 1;
    font-size: 0.9rem;
  }
  .col-delete-form button:hover {
    background: #ffffff4d;
  }

  /* "Add Collection" ghost card, appended at the end of the grid */
 .col-card--new {
    background: transparent;
    border: 2px dashed #f4e8d366;
    box-shadow: none;
    height: 190px;
}
.col-card--new:hover {
    background: #f4e8d31a;
    box-shadow: none;
}
.col-card--new .col-card-spark,
.col-card--new .col-card-title {
    color: var(--sage-100);
    opacity: 0.85;
}

  @media (max-width: 768px) {
    .col-header-row { padding: 2rem 1.5rem 1.5rem; gap: 1.1rem; }
    .col-title { font-size: 2rem; }
    .col-avatar { width: 90px; height: 90px; border-width: 4px; }
    .col-content { padding: 1.5rem 1.5rem 2.5rem; }
  }
</style>
</head>
<body>

<!-- HEADER -->
<header class="bm-header">
  <a href="homeFront.php" class="bm-wordmark">🕮 BookMarked<span class="dot">.</span></a>
</header>

<!-- Avatar + page title -->
<div class="col-header-row">
  <div class="col-avatar">
    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
  </div>
  <h1 class="col-title" id="collectionsTitle"><span class="spark">&#10022;</span> <span id="collectionsUsername"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>&rsquo;s Collections <span class="spark">&#10022;</span></h1>
</div>

<!-- Secondary toolbar: reuses the .lib-toolbar / .lib-nav styling from library.php -->
<div class="lib-toolbar">
  <nav class="lib-nav">
    <a href="library.php"><span class="spark">&#10022;</span> Back</a>
    <a href="wishlist.php"><span class="spark">&#10022;</span> Wishlist</a>
  </nav>
</div>

<!-- Main content -->
<main class="col-content">

  <form action="collections.php" method="post" class="col-create-form" id="createCollectionForm">
    <input type="text" name="collectionName" placeholder="Collection Name" required>
    <button type="submit" name="createCollection" class="col-add-link">Create</button>
  </form>

  <div class="col-grid" id="collectionsGrid">
    <?php if (mysqli_num_rows($result) > 0): ?>
      <?php while ($row = mysqli_fetch_array($result)):
          $collection_id = $row['collection_id'];
          $collection_name = $row['collection_name'];
      ?>
        <div class="col-card-wrap">
          <a href="insidecollections.php?id=<?php echo urlencode($collection_id); ?>" class="col-card">
            <span class="col-card-spark">&#10022;</span>
            <span class="col-card-title"><?php echo htmlspecialchars($collection_name); ?></span>
          </a>
          <form method="post" class="col-delete-form" onsubmit="return confirm('Delete this collection?');">
            <input type="hidden" name="delete_collection" value="<?php echo (int)$collection_id; ?>">
            <button type="submit" title="Delete collection">&times;</button>
          </form>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>

    <button type="button" class="col-card col-card--new" id="addCollectionBtn">
      <span class="col-card-spark">+</span>
      <span class="col-card-title">New Collection</span>
    </button>
  </div>

  <p class="bm-empty-state <?php echo mysqli_num_rows($result) > 0 ? 'd-none' : ''; ?>" id="collectionsEmptyState">You don&rsquo;t have any collections yet — start one to group your favorite reads.</p>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const addCollectionBtn = document.getElementById('addCollectionBtn');
  const createForm = document.getElementById('createCollectionForm');

  addCollectionBtn.addEventListener('click', function () {
    createForm.classList.toggle('open');
  });

  function fitCardTitles() {
    document.querySelectorAll('.col-card-title').forEach(function (title) {
      let fontSize = 1.6;
      const minFontSize = 0.85;
      title.style.fontSize = fontSize + 'rem';

      while (title.scrollHeight > title.clientHeight && fontSize > minFontSize) {
        fontSize -= 0.1;
        title.style.fontSize = fontSize + 'rem';
      }
    });
  }

  fitCardTitles();
});
</script>
</body>
</html>