<?php
    session_start();
    $msg = "";

    if (isset($_POST['login']))
    {
        $username = trim($_POST['username']);
        $pass = $_POST['pass'];

        include 'connection.php';

        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $numrows = mysqli_num_rows($result);

        if ($numrows == 1)
        {
            $row = mysqli_fetch_array($result);

            if (password_verify($pass, $row['password']))
            {
                $_SESSION['user'] = $row;

                if (isset($_POST['remember']))
                {
                    $selector = bin2hex(random_bytes(12));
                    $validator = bin2hex(random_bytes(32));
                    $validator_hash = hash('sha256', $validator);
                    $expires_at = date("Y-m-d H:i:s", time() + 60 * 60 * 24 * 30); // 30 days

                    $token_sql = "INSERT INTO remember_tokens (selector, validator_hash, user_id, expires_at) VALUES (?, ?, ?, ?)";
                    $token_stmt = mysqli_prepare($conn, $token_sql);
                    mysqli_stmt_bind_param($token_stmt, "ssis", $selector, $validator_hash, $row['user_id'], $expires_at);
                    mysqli_stmt_execute($token_stmt);
                    mysqli_stmt_close($token_stmt);

                    setcookie(
                        "remember_me",
                        $selector . ":" . $validator,
                        time() + 60 * 60 * 24 * 30,
                        "/",
                        "",
                        true,   // secure — requires HTTPS
                        true    // httponly
                    );
                }

                header("location: homeFront.php");
                exit;
            }
            else
            {
                $msg = "Invalid login credentials.";
            }
        }
        else
        {
            $msg = "Invalid login credentials.";
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in · BookMarked</title>

<!-- Bootstrap 5 CSS only — used for the col / d-none / d-flex utility
     classes, no Bootstrap JS is needed on this page -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="auth-styleLoader.css">
</head>
<body>

<div class="auth-shell">

    <div class="auth-brand col-lg-5 d-none d-lg-flex">
        <a href="login.php" class="auth-wordmark">🕮 BookMarked<span class="dot">.</span></a>

        <div class="auth-brand-copy">
            <svg class="auth-swoosh" width="140" height="36" viewBox="0 0 140 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 26C20 6 60 2 88 8C110 13 130 20 136 30" stroke="#85a094" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <h1>Welcome back to your worlds.</h1>
            <p>Pick up right where you left off &mdash; your listings, wishlist, and library are exactly as you left them.</p>
        </div>

        <div class="auth-brand-foot">&copy; <?php echo date("Y"); ?> 🕮 BookMarked. All rights reserved.</div>
    </div>

    <div class="auth-form-side">
        <div class="auth-card">

            <a href="login.php" class="auth-wordmark d-lg-none d-inline-block mb-4" style="color:#2b1d12;">
                🕮 BookMarked<span class="dot" style="color:#85a094;">.</span>
            </a>

            <h2>Log in</h2>
            <p class="auth-subtitle">Enter your details to access your account.</p>

            <?php if (!empty($msg)): ?>
                <div class="auth-status auth-status-error" role="alert">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post">

                <div class="mb-3">
                    <label for="loginUsername" class="auth-label">Username</label>
                    <input
                        type="text"
                        class="auth-input"
                        id="loginUsername"
                        name="username"
                        placeholder="Your username"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="mb-2">
                    <label for="loginPassword" class="auth-label">Password</label>
                    <div class="pw-field-wrap">
                        <input
                            type="password"
                            class="auth-input"
                            id="loginPassword"
                            name="pass"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" data-target="loginPassword" aria-label="Show password"></button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-auth-primary">
                    Log in
                </button>

            </form>

            <p class="auth-footline">
                Don't have an account? <a href="register.php">Sign up</a>
            </p>

        </div>
    </div>

</div>

</body>
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
</html>