<?php 
    $host = "localhost";
    $dbuser = "root";
    $dbpass = "L3tsD0SQL1!"; //change this password to the root password used in your device
    $dbname = "bookmarked";

    $conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

    if (mysqli_connect_errno()){
        echo "Connection failed: " . mysqli_connect_error();
    }
?>