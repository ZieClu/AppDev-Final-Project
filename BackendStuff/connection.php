<?php 
    $host = "localhost";
    $dbuser = "root";
    $dbpass = "L3tsD0SQL1!";
    $dbname = "bookmarked";

    $conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

    if (mysqli_connect_errno()){
        echo "Connection failed: " . msqli_connect_error();
    }
?>