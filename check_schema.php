<?php
include 'config.php';
$res = $conn->query('SHOW COLUMNS FROM scores');
while($row = $res->fetch_assoc()){
    echo $row['Field'] . "\n";
}
?>
