<?php

header('Content-Type: application/json'); 
header('Access-Control-Allow-Origin: *');

$db_server = 'localhost';
$db_user = 'root';
$port = '3308'; 
$db_pass = '';
$db_name = 'glowlinkp_db';

try {
   
    $con = mysqli_connect($db_server, $db_user, $db_pass, $db_name, $port);
} catch(mysqli_sql_exception $e) {
  
    die(json_encode(["error" => "Not Connected"])); 
}

$sql = "SELECT * FROM products LIMIT 200"; 
$result = mysqli_query($con, $sql);

$data = array();
if ($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

echo json_encode($data);

mysqli_close($con);
?>