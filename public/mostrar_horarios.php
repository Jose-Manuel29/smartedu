<?php
include 'conexion.php';

$conditions = [];
$params = [];

// Construcción dinámica de filtros
if (!empty($_GET['nrc'])) {
    $conditions[] = "NRC = ?";
    $params[] = $_GET['nrc'];
}
if (!empty($_GET['secc'])) {
    $conditions[] = "secc = ?";
    $params[] = $_GET['secc'];
}
if (!empty($_GET['materia'])) {
    $conditions[] = "id_materia = ?";
    $params[] = $_GET['materia'];
}
if (!empty($_GET['profesor'])) {
    $conditions[] = "id_profesor = ?";
    $params[] = $_GET['profesor'];
}
if (!empty($_GET['dias'])) {
    $conditions[] = "dias = ?";
    $params[] = $_GET['dias'];
}
if (!empty($_GET['hora'])) {
    $conditions[] = "hora = ?";
    $params[] = $_GET['hora'];
}

$sql = "SELECT H.NRC, H.secc, H.dias, H.hora, 
               M.nombre AS materia, 
               P.nombre AS profesor, 
               S.nombre AS salon
        FROM Horario H
        JOIN Materia M ON H.id_materia = M.id
        JOIN Profesor P ON H.id_profesor = P.id
        JOIN Salon S ON H.id_salon = S.id";

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = sqlsrv_query($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados de Horarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h4 class="mb-3 text-center">📅 Resultados del filtro</h4>
    <a href="filtros.php" class="btn btn-secondary mb-3">Volver</a>

    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>NRC</th>
                <th>Materia</th>
                <th>Profesor</th>
                <th>Sección</th>
                <th>Días</th>
                <th>Hora</th>
                <th>Salón</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    echo "<tr>
                        <td>{$row['NRC']}</td>
                        <td>{$row['materia']}</td>
                        <td>{$row['profesor']}</td>
                        <td>{$row['secc']}</td>
                        <td>{$row['dias']}</td>
                        <td>{$row['hora']}</td>
                        <td>{$row['salon']}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No se encontraron resultados</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</body>
</html>
