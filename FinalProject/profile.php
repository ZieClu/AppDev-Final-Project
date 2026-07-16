<?php
    session_start();
    require 'autologin.php';

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    if (empty($_SESSION['user']['profile_picture']))
    {
        $photo_dir = "profilepictures/default.png";
    } else {
        $photo_dir = $_SESSION['user']['profile_picture'];
    }

    $msg = "";

    if (isset($_POST['upload']))
    {
        $filename = "profilepictures/" . $_SESSION['user']['user_id'] . $_FILES['profile_photo']['name'];

        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $filename))
        {
            require 'connection.php';

            $user_id = $_SESSION['user']['user_id'];

            $sql = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $filename, $user_id);
            $result = mysqli_stmt_execute($stmt);

            if ($result)
            {
                $_SESSION['user']['profile_picture'] = $filename;
                $photo_dir = $filename;
                $msg = "Profile photo uploaded successfully.";
            } else {
                $msg = "Error while uploading profile photo.";
            }

            mysqli_stmt_close($stmt);
            mysqli_close($conn);
        } else {
            $msg = "Error while uploading profile photo.";
        }
    }

    if (isset($_POST['changepass']))
    {
        header("location: changePassword.php");
        exit;
    }

    $username_msg = "";

    if (isset($_POST['update_username']))
    {
        require 'connection.php';

        $new_username = trim($_POST['new_username']);
        $user_id = $_SESSION['user']['user_id'];

        if ($new_username === "")
        {
            $username_msg = "Username cannot be empty.";
        }
        elseif ($new_username !== $_SESSION['user']['username'])
        {
            $check_sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "si", $new_username, $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0)
            {
                $username_msg = "That username is already taken.";
            }
            else
            {
                $sql = "UPDATE users SET username = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $new_username, $user_id);

                if (mysqli_stmt_execute($stmt))
                {
                    $_SESSION['user']['username'] = $new_username;
                    $username_msg = "Username updated successfully.";
                }
                else
                {
                    $username_msg = "Error updating username. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check_stmt);
        }

        mysqli_close($conn);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as login/signup/home -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">
<link rel="stylesheet" href="profile-style.css">
</head>
<body>

<!-- HEADER (shared with home.php) -->
<header class="bm-header">
  <a href="homeFront.php" class="bm-wordmark" id="homeLogoLink">🕮 BookMarked<span class="dot">.</span></a>
</header>

<!-- LAYOUT -->
<div class="pf-layout">

  <!-- Left panel: profile identity + nav -->
  <aside class="pf-profile-panel">

    <div class="pf-avatar">
      <img src="<?php echo htmlspecialchars($photo_dir); ?>" alt="" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
    </div>

    <form method="post" id="usernameForm" class="pf-username-form">
      <input
        type="text"
        name="new_username"
        id="usernameInput"
        class="pf-username-input"
        value="<?php echo htmlspecialchars($_SESSION['user']['username']); ?>"
        readonly
        required
      >
      <input type="hidden" name="update_username" value="1">
    </form>

    <!-- Backend now exists (see update_username handling above) — the link
         toggles the field above between read-only and editable, and submits
         the form on the second click, same "reveal + submit" pattern as the
         profile picture form below. -->
    <div class="pf-edit-username">
      <span class="icon">&#9998;</span>
      <a href="#" id="editUsernameLink">Edit username</a>
    </div>

    <?php if (!empty($username_msg)): ?>
      <p style="color: var(--teal-800); font-size: 0.9rem; margin-top: -0.8rem; margin-bottom: 1.2rem;"><?php echo htmlspecialchars($username_msg); ?></p>
    <?php endif; ?>

    <!-- Real upload form, matching the working logic in profile.php.
         "Change Profile Picture" submits this form via a hidden file input
         triggered by the link, instead of the mockup's inert JS stub. -->
    <form method="post" enctype="multipart/form-data" id="profilePicForm">
        <input type="file" name="profile_photo" id="profilePhotoInput" required style="display:none;" onchange="document.getElementById('profilePicForm').submit();">
        <div class="pf-change-pic">
          <a href="#" id="changePicLink">Change Profile Picture</a>
        </div>
        <input type="hidden" name="upload" value="1">
    </form>

    <?php if (!empty($msg)): ?>
      <p style="color: var(--teal-800); font-size: 0.9rem; margin-top: -1.2rem; margin-bottom: 1.2rem;"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <hr class="pf-divider">

    <nav class="pf-nav">
      <a href="library.php"><span class="spark">&#10022;</span> My Library</a>
      <a href="userTransactionTable.php"><span class="spark">&#10022;</span> Transaction History</a>
      <a href="inventory.php"><span class="spark">&#10022;</span> My Store</a>
      <a href="userPurchaseTable.php"><span class="spark">&#10022;</span> Purchase History</a>
    </nav>

    <hr class="pf-divider">

    <div class="pf-footer-links">
      <a href="homeFront.php">Back to Store</a>
      <a href="logout.php">Logout?</a>
    </div>

  </aside>

  <!-- Right panel: account information -->
  <main class="pf-info-panel">
    <h2 class="pf-info-heading"><span class="spark">&#10022;</span> Account Information</h2>

    <!-- No backend exists yet for editing these fields — displayed as
         read-only, matching the original profile.php table, rather than
         implying an edit feature that isn't wired up. -->
    <div class="pf-field">
      <label for="firstName">First Name</label>
      <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($_SESSION['user']['first_name']); ?>" readonly>
    </div>

    <div class="pf-field">
      <label for="lastName">Last Name</label>
      <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($_SESSION['user']['last_name']); ?>" readonly>
    </div>

    <div class="pf-field">
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" readonly>
    </div>

    <div class="pf-field pf-field-birthdate">
      <label for="birthdate">Birthdate</label>
      <input type="text" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars(date("F d, Y", strtotime($_SESSION['user']['birthdate']))); ?>" readonly>
      <span class="cal-icon">&#128197;</span>
    </div>

    <div class="pf-field pf-field-password">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" value="***********" readonly>
      <form action="profile.php" method="post" style="display:inline;">
        <button type="submit" name="changepass" class="pf-change-password" style="background:none; border:none; padding:0; cursor:pointer;">Change Password?</button>
      </form>
    </div>
  </main>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const editUsernameLink = document.getElementById('editUsernameLink');
  const usernameInput = document.getElementById('usernameInput');
  const usernameForm = document.getElementById('usernameForm');
  const changePicLink = document.getElementById('changePicLink');
  const profilePhotoInput = document.getElementById('profilePhotoInput');

  // First click unlocks the field for editing; second click submits it.
  editUsernameLink.addEventListener('click', function (e) {
  e.preventDefault();

    if (usernameInput.readOnly) {
      usernameInput.readOnly = false;
      usernameInput.classList.add('editing');
      usernameInput.focus();
      usernameInput.select();
      editUsernameLink.textContent = 'Save username';
    } else {
      usernameForm.submit();
    }
  });

  // Clicking "Change Profile Picture" opens the real (hidden) file input;
  // choosing a file auto-submits the upload form (see onchange above).
  changePicLink.addEventListener('click', function (e) {
    e.preventDefault();
    profilePhotoInput.click();
  });
});
</script>
</body>
</html>