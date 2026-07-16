<?php
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_me']))
{
    include 'connection.php';

    list($selector, $validator) = explode(":", $_COOKIE['remember_me'], 2);

    $sql = "SELECT rt.*, u.* FROM remember_tokens rt
            JOIN users u ON u.user_id = rt.user_id
            WHERE rt.selector = ? AND rt.expires_at >= NOW()";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $selector);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result))
    {
        if (hash_equals($row['validator_hash'], hash('sha256', $validator)))
        {
            $_SESSION['user'] = $row;

            // rotate the token so a captured cookie can't be reused
            $delete_sql = "DELETE FROM remember_tokens WHERE selector = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "s", $selector);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);

            $new_selector = bin2hex(random_bytes(12));
            $new_validator = bin2hex(random_bytes(32));
            $new_hash = hash('sha256', $new_validator);
            $expires_at = date("Y-m-d H:i:s", time() + 60 * 60 * 24 * 30);

            $insert_sql = "INSERT INTO remember_tokens (selector, validator_hash, user_id, expires_at) VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ssis", $new_selector, $new_hash, $row['user_id'], $expires_at);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);

            setcookie("remember_me", $new_selector . ":" . $new_validator, time() + 60 * 60 * 24 * 30, "/", "", true, true);
        }
        else
        {
            setcookie("remember_me", "", time() - 3600, "/");
        }
    }
    else
    {
        setcookie("remember_me", "", time() - 3600, "/");
    }

    mysqli_close($conn);
}