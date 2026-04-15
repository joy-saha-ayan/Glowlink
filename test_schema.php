<?php
$db=new PDO('mysql:host=localhost;dbname=glowlinkp_db','root','');
foreach($db->query('DESCRIBE users') as $r) {
    echo $r['Field'].' '.$r['Type'].PHP_EOL;
}
