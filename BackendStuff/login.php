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
            header("location: home.php");
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

<h2>Login</h2>

<form method="post">
    <input type="text" name="username" placeholder="Username"><br>
    <input type="password" name="pass" placeholder="Password"><br>
    <input type="submit" value="Login" name="login">
</form>

<?php echo $msg; ?>

No account? <a href="register.php">Signup here. </a>