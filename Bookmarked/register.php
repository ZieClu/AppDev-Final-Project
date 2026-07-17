<?php 
    $msg = "";

    if (isset($_POST['signup'])) {
        $username = trim($_POST['username']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $birthdate = $_POST['birthday'];
        $agree_terms = isset($_POST['agreeTerms']);

        $emailRegex = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';
        $passwordRegex = '/^(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,30}$/';

        if (!$agree_terms)
        {
            $msg = "You must agree to the Terms of Service and Privacy Policy.";
        }

        elseif ($username === "" || $first_name === "" || $last_name === "" || $email === "" || $password === "" || $confirm_password === "" || $birthdate === "")
        {
            $msg = "Please fill in all fields.";
        } 

        elseif (!preg_match($emailRegex, $email))
        {
            $msg = "Please enter a valid email address.";
        }

        elseif (!preg_match($passwordRegex, $password))
        {
            $msg = "Password must be 8-30 characters and include at least one number and one special character.";
        }
        
        elseif ($password !== $confirm_password)
        {
            $msg = "Passwords do not match!";
        }
        else
        {
        require 'connection.php';

        $check_username_sql = "SELECT user_id FROM users WHERE username = ?";
        $check_username_stmt = mysqli_prepare($conn, $check_username_sql);
        mysqli_stmt_bind_param($check_username_stmt, "s", $username);
        mysqli_stmt_execute($check_username_stmt);
        mysqli_stmt_store_result($check_username_stmt);
        $username_taken = mysqli_stmt_num_rows($check_username_stmt) > 0;
        mysqli_stmt_close($check_username_stmt);

        $check_email_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_email_stmt = mysqli_prepare($conn, $check_email_sql);
        mysqli_stmt_bind_param($check_email_stmt, "s", $email);
        mysqli_stmt_execute($check_email_stmt);
        mysqli_stmt_store_result($check_email_stmt);
        $email_taken = mysqli_stmt_num_rows($check_email_stmt) > 0;
        mysqli_stmt_close($check_email_stmt);

        if ($username_taken && $email_taken)
        {
            $msg = "That username and email are already registered.";
        }
        elseif ($username_taken)
        {
            $msg = "That username is already taken.";
        }
        elseif ($email_taken)
        {
            $msg = "That email is already registered.";
        }
        else
        {
            $password = password_hash($password, PASSWORD_DEFAULT);
            $defaultProfile = "profilepictures/default.png";

            $sql = "INSERT INTO users (username, first_name, last_name, email, password, birthdate, profile_picture)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssss", $username, $first_name, $last_name, $email, $password, $birthdate, $defaultProfile);
            $result = mysqli_stmt_execute($stmt);

            if ($result) {
                $msg = "Registration successful!";
                header("Location: login.php");
            } else {
                $msg = "Error signing up. Please try again.";
            }

            mysqli_stmt_close($stmt);
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
<title>Sign up · BookMarked</title>

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
        <a href="register.php" class="auth-wordmark">🕮 BookMarked<span class="dot">.</span></a>

        <div class="auth-brand-copy">
            <svg class="auth-swoosh" width="140" height="36" viewBox="0 0 140 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 26C20 6 60 2 88 8C110 13 130 20 136 30" stroke="#85a094" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <h1>A home for every book.</h1>
            <p>List what you've finished, find what's next, and keep it all in one shelf &mdash; free to start, ready when you are.</p>
        </div>

        <div class="auth-brand-foot">&copy; <?php echo date("Y"); ?> 🕮 BookMarked. All rights reserved.</div>
    </div>

    <div class="auth-form-side">
        <div class="auth-card">

            <a href="register.php" class="auth-wordmark d-lg-none d-inline-block mb-4" style="color:#2b1d12;">
                🕮 BookMarked<span class="dot" style="color:#85a094;">.</span>
            </a>

            <h2>Create your account</h2>
            <p class="auth-subtitle">Start buying and selling books in a few seconds.</p>

            <?php if (!empty($msg)): ?>
            <div class="auth-status auth-status-error" role="alert">
                <?php echo htmlspecialchars($msg); ?>
            </div>
            <?php endif; ?>

            <form action="register.php" method="post">

                <div class="mb-3">
                    <label for="signupUsername" class="auth-label">Username</label>
                    <input
                        type="text"
                        class="auth-input"
                        id="signupUsername"
                        name="username"
                        placeholder="Choose a username"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="d-flex gap-3 mb-3">
                    <div class="flex-fill">
                        <label for="signupFirstName" class="auth-label">First name</label>
                        <input
                            type="text"
                            class="auth-input"
                            id="signupFirstName"
                            name="first_name"
                            placeholder="Jamie"
                            autocomplete="given-name"
                            required
                        >
                    </div>
                    <div class="flex-fill">
                        <label for="signupLastName" class="auth-label">Last name</label>
                        <input
                            type="text"
                            class="auth-input"
                            id="signupLastName"
                            name="last_name"
                            placeholder="Rivera"
                            autocomplete="family-name"
                            required
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label for="signupEmail" class="auth-label">Email address</label>
                    <input
                        type="email"
                        class="auth-input"
                        id="signupEmail"
                        name="email"
                        placeholder="you@example.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="signupBirthday" class="auth-label">Birthdate</label>
                    <input
                        type="date"
                        class="auth-input"
                        id="signupBirthday"
                        name="birthday"
                        autocomplete="bday"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="signupPassword" class="auth-label">Password</label>
                    <div class="pw-field-wrap">
                        <input
                            type="password"
                            class="auth-input"
                            id="signupPassword"
                            name="password"
                            placeholder="Create a password"
                            autocomplete="new-password"
                            minlength="8"
                            maxlength="30"
                            pattern="(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,30}"
                            title="8-30 characters, at least one number and one special character"
                            required
                        >
                        <button type="button" class="toggle-password" data-target="signupPassword" aria-label="Show password"></button>
                    </div>
                    <small class="auth-hint">8&ndash;30 characters, at least one number and one special character.</small>
                </div>

                <div class="mb-3">
                    <label for="signupConfirmPassword" class="auth-label">Confirm password</label>
                    <div class="pw-field-wrap">
                        <input
                            type="password"
                            class="auth-input"
                            id="signupConfirmPassword"
                            name="confirm_password"
                            placeholder="Re-enter your password"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="toggle-password" data-target="signupConfirmPassword" aria-label="Show password"></button>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms" required>
                    <label class="form-check-label" for="agreeTerms">
                        I agree to the Terms of Service and Privacy Policy  
                    </label>
                </div>

                <button type="submit" name="signup" class="btn-auth-primary">
                    Create account
                </button>

            </form>

            <p class="auth-footline">
                Already have an account? <a href="login.php">Log in</a>
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