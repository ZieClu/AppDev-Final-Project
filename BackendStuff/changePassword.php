<?php
    session_start();

    $msg = $_SESSION['flash_msg'] ?? "";
    unset($_SESSION['flash_msg']);

    if (!isset($_SESSION['user']))
    {
        header("location: login.php");
        exit;
    }

    $msg = "";

    if(isset($_POST['change_password']))
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

<form action="changePassword.php" method="post">
    <input type="password" name="current_password" placeholder="Current Password"/> <br>
    <input type="password" name="new_password" placeholder="New Password"/> <br>
    <input type="password" name="confirm_password" placeholder="Re-type Password"/> <br>
    <input type="submit" name="change_password" value="Change Password" /> <br>
</form>

<?php echo $msg; ?>