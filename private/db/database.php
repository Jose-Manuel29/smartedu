<?php
// Archivo: private/db/database.php
// Provee conexión única usando ext/sqlsrv y helpers usados en el repo

$serverName = "localhost";
$connectionOptions = [
    "Database" => "SMARTEDU",
    "Uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    error_log("[database.php] Error en la conexión SQLSRV: " . print_r(sqlsrv_errors(), true));
    die("Error de conexión a la base de datos. Revisa la configuración.");
}

function get_db_connection()
{
    global $conn;
    return $conn;
}

// Generar UUID v4 (usado por el flujo de carga)
function generar_uuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variante RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Nota: no cerramos la etiqueta PHP para evitar espacios en salida.