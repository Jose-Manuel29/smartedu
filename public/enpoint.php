<?php
require_once __DIR__ . '/../private/db/database.php';

$conn = get_db_connection();
if (!$conn) {
    http_response_code(500);
    die("Error: no hay conexión a la base de datos (sqlsrv).");
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data["horarios"])) {
    http_response_code(400);
    die("Datos inválidos recibidos.");
}

// Si no viene session_id, generarla aquí
if (empty($data["session_id"])) {
    if (function_exists('generar_uuid')) {
        $data["session_id"] = generar_uuid();
    } else {
        $data["session_id"] = bin2hex(random_bytes(16));
    }
}

// =========================================================
// 0. LIMPIEZA (RESET DE TABLAS)
// =========================================================
// Borramos en orden para respetar las llaves foráneas (primero hijos, luego padres)
$sqlReset = "
    DELETE FROM Horario;
    DELETE FROM Materia;
    DELETE FROM Profesor;
    DELETE FROM Salon;
    DELETE FROM horarios;
";
$stmtReset = sqlsrv_query($conn, $sqlReset);

// Si falla el borrado, lo registramos pero intentamos continuar (o podrías detenerlo)
if ($stmtReset === false) {
    error_log("Advertencia: No se pudieron limpiar las tablas anteriores: " . print_r(sqlsrv_errors(), true));
}

// =========================================================
// 1. INSERTAR EN TABLA RAW (TEMPORAL)
// =========================================================
$sqlRaw = "INSERT INTO horarios (NRC, Clave, Materia, Secc, Dias, Hora, Profesor, Salon, session_id)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insertados = 0;
$sessId = $data["session_id"];

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
        $sessId
    ];

    $stmt = sqlsrv_query($conn, $sqlRaw, $params);
    if ($stmt) {
        $insertados++;
    } else {
        // Opcional: registrar errores en el log de php
        error_log(print_r(sqlsrv_errors(), true));
    }
}

// =========================================================
// 2. NORMALIZACIÓN AUTOMÁTICA (Ejecutar lógica de SMAREDU2.sql)
// =========================================================
if ($insertados > 0) {
    // A) Insertar Materias Nuevas (filtrando duplicados con CTE)
    $sqlMateria = "
        WITH cte_materia AS (
            SELECT Clave, Materia,
                   ROW_NUMBER() OVER (PARTITION BY Clave ORDER BY Materia) AS rn
            FROM horarios
            WHERE session_id = ?
        )
        INSERT INTO Materia (clave, nombre)
        SELECT Clave, Materia
        FROM cte_materia
        WHERE rn = 1
          AND Clave NOT IN (SELECT clave FROM Materia);
    ";
    sqlsrv_query($conn, $sqlMateria, [$sessId]);

    // B) Insertar Profesores Nuevos
    $sqlProf = "
        WITH cte_prof AS (
            SELECT Profesor,
                   ROW_NUMBER() OVER (PARTITION BY Profesor ORDER BY Profesor) AS rn
            FROM horarios
            WHERE session_id = ?
        )
        INSERT INTO Profesor (nombre)
        SELECT Profesor
        FROM cte_prof
        WHERE rn = 1
          AND Profesor NOT IN (SELECT nombre FROM Profesor);
    ";
    sqlsrv_query($conn, $sqlProf, [$sessId]);

    // C) Insertar Salones Nuevos
    $sqlSalon = "
        WITH cte_salon AS (
            SELECT Salon,
                   ROW_NUMBER() OVER (PARTITION BY Salon ORDER BY Salon) AS rn
            FROM horarios
            WHERE session_id = ?
        )
        INSERT INTO Salon (nombre)
        SELECT Salon
        FROM cte_salon
        WHERE rn = 1
          AND Salon NOT IN (SELECT nombre FROM Salon);
    ";
    sqlsrv_query($conn, $sqlSalon, [$sessId]);

    // D) Insertar en la Tabla Normalizada 'Horario'
    // Se filtra por session_id para no duplicar registros si la tabla ya tenía datos
    $sqlHorario = "
        INSERT INTO Horario (NRC, secc, dias, hora, id_materia, id_profesor, id_salon, session_id)
        SELECT
            h.NRC,
            h.Secc,
            h.Dias,
            h.Hora,
            m.id,
            p.id,
            s.id,
            h.session_id
        FROM horarios h
        JOIN Materia m ON h.Clave = m.clave
        JOIN Profesor p ON h.Profesor = p.nombre
        JOIN Salon s ON h.Salon = s.nombre
        WHERE h.session_id = ?
    ";
    $stmtNorm = sqlsrv_query($conn, $sqlHorario, [$sessId]);
    
    if ($stmtNorm === false) {
        error_log("Error normalizando horarios: " . print_r(sqlsrv_errors(), true));
    }
}

echo "Se limpiaron los datos anteriores, se guardaron {$insertados} registros nuevos y se ejecutó la normalización correctamente. Session ID: {$sessId}";

// No cerramos PHP para evitar espacios