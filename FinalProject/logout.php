<?php
session_start();

if (isset($_COOKIE['remember_me']))
{
    include 'connection.php';
    list($selector, ) = explode(":", $_COOKIE['remember_me'], 2);

    $sql = "DELETE FROM remember_tokens WHERE selector = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $selector);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    setcookie("remember_me", "", time() - 3600, "/");
}

session_destroy();
header("location: login.php");
exit;
?>