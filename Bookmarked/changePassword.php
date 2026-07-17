<?php
    session_start();
    require 'autologin.php';

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    $msg = "";

    if (isset($_POST['change_password']))
    {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        require 'connection.php';

        $user_id = $_SESSION['user']['user_id'];

        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_array($result);
        mysqli_stmt_close($stmt);

        if (!$row || !password_verify($current_password, $row['password']))
        {
            $msg = "Current password is incorrect.";
        }
        elseif ($new_password !== $confirm_password)
        {
            $msg = "New password and confirm password do not match.";
        }
        else
        {
            $password = password_hash($new_password, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $password, $user_id);
            if (mysqli_stmt_execute($stmt))
            {
                $_SESSION['flash_msg'] = "Password changed successfully.";
                header("location: profile.php");
                exit;
            }
            else
            {
                $msg = "Error changing password. Please try again.";
            }
            mysqli_close($conn);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as the rest of the site -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">

<style>
  /* --- Change Password page — extends bookmarked-style.css tokens, same
       pattern as inventory.php's inline <style> block --- */

  .cp-content {
    position: relative;
    z-index: 1;
    max-width: 480px;
    margin: 3.5rem auto;
    padding: 0 1.5rem;
  }

  .cp-card {
    background: var(--cream-50);
    border-radius: var(--radius-lg);
    padding: 2.5rem 2.25rem;
    box-shadow: 0 30px 60px -20px #2b1d1259;
  }

  .cp-title {
    font-family: var(--font-display);
    font-size: 1.9rem;
    color: var(--teal-900);
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin: 0 0 0.5rem;
  }

  .cp-title .spark { color: var(--teal-800); }

  .cp-divider {
    border: none;
    border-top: 2px solid #2b1d124d;
    margin: 0 0 1.5rem;
  }

  .cp-msg {
    font-weight: 600;
    font-size: 0.92rem;
    padding: 0.7rem 0.9rem;
    border-radius: var(--radius-sm);
    margin-bottom: 1.25rem;
    background: rgba(179, 73, 47, 0.1);
    color: var(--danger);
    border: 1px solid rgba(179, 73, 47, 0.25);
  }

  .cp-form {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
  }

  .cp-form label {
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--ink);
    margin-bottom: 0.35rem;
    display: block;
  }

  .cp-form input {
    border: 1.5px solid #2b1d1226;
    border-radius: var(--radius-sm);
    padding: 0.7rem 0.9rem;
    font-size: 0.96rem;
    background: #fff;
    color: var(--ink);
    width: 100%;
  }

  .cp-form input:focus {
    outline: none;
    border-color: var(--sage-500);
    box-shadow: 0 0 0 3px #85a09440;
  }

  .cp-submit-btn {
    background: var(--brown-800);
    color: var(--cream-100);
    border: none;
    border-radius: var(--radius-sm);
    padding: 0.75rem 1rem;
    font-weight: 600;
    font-size: 0.98rem;
    margin-top: 0.5rem;
    transition: background 0.15s ease;
  }
  .cp-submit-btn:hover { background: #4a3722; }

  .cp-back-link {
    display: inline-block;
    margin-top: 1.5rem;
    color: var(--teal-900);
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 3px;
  }
  .cp-back-link:hover { color: var(--teal-800); }
</style>
</head>
<body>

<!-- HEADER (shared across the site) -->
<header class="bm-header">
  <a href="homeFront.php" class="bm-wordmark" id="homeLogoLink">🕮 BookMarked<span class="dot">.</span></a>
</header>

<main class="cp-content">
  <div class="cp-card">
    <h1 class="cp-title"><span class="spark">&#10022;</span> Change Password</h1>
    <hr class="cp-divider">

    <?php if (!empty($msg)): ?>
      <p class="cp-msg"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <form action="changePassword.php" method="post" class="cp-form">
    <div>
      <label for="currentPassword">Current Password</label>
      <div class="pw-field-wrap">
        <input type="password" id="currentPassword" name="current_password" required>
        <button type="button" class="toggle-password" data-target="currentPassword" aria-label="Show password"></button>
      </div>
    </div>

    <div>
      <label for="newPassword">New Password</label>
      <div class="pw-field-wrap">
        <input type="password" id="newPassword" name="new_password" required>
        <button type="button" class="toggle-password" data-target="newPassword" aria-label="Show password"></button>
      </div>
    </div>

    <div>
      <label for="confirmPassword">Re-type New Password</label>
      <div class="pw-field-wrap">
        <input type="password" id="confirmPassword" name="confirm_password" required>
        <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Show password"></button>
      </div>
    </div>

    <button type="submit" name="change_password" class="cp-submit-btn">Change Password</button>
  </form>

    <a href="profile.php" class="cp-back-link">&larr; Back to Account</a>
  </div>
</main>
<script>
const eyeOpenIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M1.5 12C1.5 12 5 5 12 5C19 5 22.5 12 22.5 12C22.5 12 19 19 12 19C5 19 1.5 12 1.5 12Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
</svg>`;

const eyeClosedIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M3 3L21 21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
  <path d="M10.6 5.2C11.06 5.1 11.53 5 12 5C19 5 22.5 12 22.5 12C22.5 12 21.4 14.1 19.3 15.9M6.7 6.7C3.9 8.4 1.5 12 1.5 12C1.5 12 5 19 12 19C13.5 19 14.8 18.7 15.9 18.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M9.9 9.9C9.34 10.46 9 11.19 9 12C9 13.66 10.34 15 12 15C12.81 15 13.54 14.66 14.1 14.1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
</svg>`;

document.querySelectorAll('.toggle-password').forEach(function (toggle) {
  toggle.innerHTML = eyeOpenIcon;
  toggle.setAttribute('aria-label', 'Show password');

  toggle.addEventListener('click', function () {
    const input = document.getElementById(toggle.dataset.target);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    toggle.innerHTML = isHidden ? eyeClosedIcon : eyeOpenIcon;
    toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
  });
});
</script>

</body>
</html>