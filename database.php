<?php
header("Content-Type: text/plain; charset=utf-8");

// Conexión a SQL Server
$serverName = "localhost"; 
$connectionOptions = [
    "Database" => "SMARTEDU",
    "Uid" => "",          // tu usuario
    "PWD" => "",          // tu contraseña
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Leer los datos enviados desde index.html
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data["horarios"])) {
    die("Datos inválidos recibidos.");
}

// 🔹 Generar un session_id tipo UUID para esta carga completa
function generar_uuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variante RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$session_id = generar_uuid();

// 🔹 Preparar el INSERT incluyendo session_id
$sql = "INSERT INTO horarios (NRC, Clave, Materia, Secc, Dias, Hora, Profesor, Salon, session_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insertados = 0;

foreach ($data["horarios"] as $h) {
    $params = [
        $h["NRC"],
        $h["Clave"],
        $h["Materia"],
        $h["Secc"],
        $h["Días"],
        $h["Hora"],
        $h["Profesor"],
        $h["Salón"],
        $session_id
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt) $insertados++;
}

sqlsrv_close($conn);

echo " Se guardaron {$insertados} horarios correctamente.\n";
echo "Session ID: {$session_id}";
?>
