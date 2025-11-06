<?php
// ================================================
// MODELO: funciones para manejar los horarios (PDO)
// ================================================

function fetchHorariosPorMaterias(PDO $pdo, array $materias): array {
    // Limpieza básica
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

    // Depuración usando error_log (no echo)
    error_log("Consulta SQL: " . $sql);
    error_log("Parámetros: " . json_encode($materias));

    try {
        // USAR PDO CORRECTAMENTE (no sqlsrv_*)
        $stmt = $pdo->prepare($sql);
        $stmt->execute($materias);

        // Recoger resultados
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Log en lugar de echo
        error_log("Total filas recuperadas: " . count($rows));

        return $rows;
    } catch (PDOException $e) {
        error_log("Error en fetchHorariosPorMaterias: " . $e->getMessage());
        return [];
    }
}