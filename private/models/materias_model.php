<?php
// ================================================
// MODELO: funciones para manejar los horarios (PDO)
// ================================================

function fetchHorariosPorMaterias($db, array $materias): array {
    // Acepta $db como PDO o como recurso sqlsrv
    $materias = array_filter(array_map('trim', $materias));
    if (empty($materias)) {
        error_log("No se recibieron materias válidas.");
        return [];
    }

    // Crear placeholders (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($materias), '?'));
    $sql = "SELECT NRC, Clave, Materia, Secc, Dias, Hora, Profesor, Salon, session_id
            FROM horarios
            WHERE Materia IN ($placeholders)";

    error_log("Consulta SQL: " . $sql);

    // PDO path
    if ($db instanceof PDO) {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($materias);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Total filas recuperadas (PDO): " . count($rows));
            return $rows;
        } catch (PDOException $e) {
            error_log("Error en fetchHorariosPorMaterias (PDO): " . $e->getMessage());
            return [];
        }
    }

    // sqlsrv path (asume $db es recurso de conexión)
    if ($db) {
        $params = $materias;
        $stmt = @sqlsrv_query($db, $sql, $params);
        if ($stmt === false) {
            error_log("Error en sqlsrv_query: " . print_r(sqlsrv_errors(), true));
            return [];
        }
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        error_log("Total filas recuperadas (sqlsrv): " . count($rows));
        return $rows;
    }

    return [];
}