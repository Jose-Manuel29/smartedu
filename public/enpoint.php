<?php
require_once __DIR__ . '/../private/db/database.php';

$conn = get_db_connection();
if (!$conn) {
    http_response_code(500);
    echo "Error: no hay conexión a la base de datos (sqlsrv).";
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data["horarios"])) {
    http_response_code(400);
    echo "Datos inválidos recibidos.";
    exit;
}

// Si no viene session_id, generarla aquí
if (empty($data["session_id"])) {
    if (function_exists('generar_uuid')) {
        $data["session_id"] = generar_uuid();
    } else {
        $data["session_id"] = bin2hex(random_bytes(16));
    }
}

$sql = "INSERT INTO horarios (NRC, Clave, Materia, Secc, Dias, Hora, Profesor, Salon, session_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insertados = 0;

foreach ($data["horarios"] as $h) {
    $params = [
        $h["NRC"] ?? null,
        $h["Clave"] ?? null,
        $h["Materia"] ?? null,
        $h["Secc"] ?? null,
        $h["Días"] ?? null,
        $h["Hora"] ?? null,
        $h["Profesor"] ?? null,
        $h["Salón"] ?? null,
        $data["session_id"]
    ];

    $stmt = @sqlsrv_query($conn, $sql, $params);
    if ($stmt) $insertados++;
}

echo "Se guardaron {$insertados} horarios correctamente. Session ID: {$data["session_id"]}";

// No se cierra la etiqueta PHP para evitar espacios en blanco indeseados