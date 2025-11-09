<?php
header("Content-Type: text/plain; charset=utf-8");


$serverName = "localhost"; 
$connectionOptions = [
    "Database" => "SMARTEDU",
    "Uid" => "",          
    "PWD" => "",          
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data["horarios"]) || !isset($data["session_id"])) {
    die("Datos inválidos recibidos.");
}

$session_id = $data["session_id"];



$sql_insert = "INSERT INTO horarios (NRC, Clave, Materia, Secc, Dias, Hora, Profesor, Salon, session_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insertados = 0;

sqlsrv_begin_transaction($conn);

try {
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
        $stmt = sqlsrv_query($conn, $sql_insert, $params);
        if ($stmt) $insertados++;
    }

    if ($insertados !== count($data["horarios"])) {
         throw new Exception("Error al insertar algunos registros.");
    }
    
    // 2. NORMALIZACIÓN DE DATOS (Tomado de SMAREDU2.sql)
    
    // a. Insertar materias únicas
    $sql_materia = "
        WITH cte_materia AS (
            SELECT Clave, Materia, ROW_NUMBER() OVER (PARTITION BY Clave ORDER BY Materia) AS rn
            FROM horarios
            WHERE session_id = ?
        )
        INSERT INTO Materia (clave, nombre)
        SELECT Clave, Materia FROM cte_materia
        WHERE rn = 1 AND Clave NOT IN (SELECT clave FROM Materia)
    ";
    sqlsrv_query($conn, $sql_materia, [$session_id]);


    // b. Insertar profesores únicos
    $sql_prof = "
        WITH cte_prof AS (
            SELECT Profesor, ROW_NUMBER() OVER (PARTITION BY Profesor ORDER BY Profesor) AS rn
            FROM horarios
            WHERE session_id = ?
        )
        INSERT INTO Profesor (nombre)
        SELECT Profesor FROM cte_prof
        WHERE rn = 1 AND Profesor NOT IN (SELECT nombre FROM Profesor)
    ";
    sqlsrv_query($conn, $sql_prof, [$session_id]);


    // c. Insertar salones únicos
    $sql_salon = "
        WITH cte_salon AS (
            SELECT Salon, ROW_NUMBER() OVER (PARTITION BY Salon ORDER BY Salon) AS rn
            FROM horarios
            WHERE session_id = ?
        )
        INSERT INTO Salon (nombre)
        SELECT Salon FROM cte_salon
        WHERE rn = 1 AND Salon NOT IN (SELECT nombre FROM Salon)
    ";
    sqlsrv_query($conn, $sql_salon, [$session_id]);
    
    
    // d. Insertar horarios normalizados
    $sql_horario = "
        INSERT INTO Horario (NRC, secc, dias, hora, id_materia, id_profesor, id_salon, session_id)
        SELECT
            h.NRC, h.Secc, h.Dias, h.Hora, m.id, p.id, s.id, h.session_id
        FROM horarios h
        JOIN Materia m ON h.Clave = m.clave
        JOIN Profesor p ON h.Profesor = p.nombre
        JOIN Salon s ON h.Salon = s.nombre
        WHERE h.session_id = ?
    ";
    sqlsrv_query($conn, $sql_horario, [$session_id]);


    sqlsrv_commit($conn);
    echo "Se guardaron {$insertados} horarios correctamente y se normalizaron los datos.\nSession ID: {$session_id}";

} catch (Exception $e) {
    sqlsrv_rollback($conn);
    http_response_code(500); // Internal Server Error
    die("Error en la transacción de la base de datos: " . $e->getMessage());
} finally {
    sqlsrv_close($conn);
}
?>