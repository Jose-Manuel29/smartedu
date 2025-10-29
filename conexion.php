<?php
$serverName = "localhost"; 
$connectionOptions = [
    "Database" => "SMARTEDU",
    "Uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];

// Conexión
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
