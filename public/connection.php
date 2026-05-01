<?php
    $db_server = 'localhost';
    $db_user = 'root';
    $port = '3308';
    $db_pass = '';
    $db_name = 'glowlinkp_db';
    
    try {
        
        $con = mysqli_connect($db_server, $db_user, $db_pass, $db_name, $port);
    } catch(mysqli_sql_exception) {
        echo "Not Connected";
        exit(); 
    }
?>