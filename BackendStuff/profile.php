<?php
    session_start();

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
?>

<h1><?php echo htmlspecialchars($_SESSION['user']['username']);?>'s Profile</h1>

<img src="<?php echo htmlspecialchars($photo_dir); ?>" alt="" width="100px">

<form method="post" enctype="multipart/form-data">
    <input type="file" name="profile_photo" required>
    <input type="submit" name="upload" value="Upload">
</form>
<?php echo $msg; ?>

<br><hr>

<h2>Account Information</h2>

<table>
    <tr>
        <td class="label">First Name</td>
        <td><?php echo htmlspecialchars($_SESSION['user']['first_name']); ?></td>
    </tr>
    <tr>
        <td class="label">Last Name</td>
        <td><?php echo htmlspecialchars($_SESSION['user']['last_name']); ?></td>
    </tr>
    <tr>
        <td class="label">Email Address</td>
        <td><?php echo htmlspecialchars($_SESSION['user']['email']); ?></td>
    </tr>
    <tr>
        <td class="label">Birthdate</td>
        <td><?php echo date("F d, Y", strtotime($_SESSION['user']['birthdate'])); ?></td>
    </tr>
    <tr>
        <td class="label">Password</td>
        <td>••••••••</td>
    </tr>
</table>

<form action="profile.php" method="post">
<input type="submit" name="changepass" value="Change Password">
</form>

<?php if (isset($_POST['changepass']))
{
    header("location: changePassword.php");
} ?>

<br><hr>

<table>
    <tr>
        <td> <a href="library.php">Visit My Library</a> </td>
        <td> <a href="userTransactionTable.php">My Transaction History</a> </td>
    </tr>
    <tr>
        <td> <a href="inventory.php">Visit My Listings</a> </td>
        <td> <a href="userPurchaseTable.php">My Purchase History</a> </td>
    </tr>
</table>

<br><hr>

<a href="home.php">Back to Store</a>
<a href="logout.php">Logout</a>