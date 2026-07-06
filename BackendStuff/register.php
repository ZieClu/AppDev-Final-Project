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

        $emailRegex = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';

        if (!preg_match($emailRegex, $email))
        {
            $msg = "Please enter a valid email address.";
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
            } else {
                $msg = "Error signing up. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_close($conn);
    }
    }
?>

<form action="register.php" method="post">
    <input type="text" name="username" placeholder="Username" /> <br>
    <input type="text" name="first_name" placeholder="First Name" /> <br>
    <input type="text" name="last_name" placeholder="Last Name" /> <br>
    <input type="email" name="email" placeholder="Email" /> <br>
    <input type="password" name="password" placeholder="Password"/> <br>
    <input type="password" name="confirm_password" placeholder="Re-type Password"/> <br>
    Birthdate: <input type="date" name="birthday"/> <br>
    <br>
    <input type="submit" name="signup" value="Signup" /> <br>
</form>

<?php echo $msg; ?>
<a href="login.php">Login</a>