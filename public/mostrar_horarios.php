<?php
//include 'conexion.php';

$conditions = [];
$params = [];

// Construcción dinámica de filtros (Tu lógica original)
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

// Ejecutar consulta
$stmt = sqlsrv_query($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- TU HOJA DE ESTILOS (El estilo Deep Space) -->
    <link href="css/estilos.css" rel="stylesheet">

    <style>
        /* Ajustes específicos para esta tabla */
        h1.main-title {
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .table thead th {
            background-color: #0d6efd; /* Azul Bootstrap */
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f5f9;
        }
        .badge-nrc {
            background-color: #e9ecef;
            color: #495057;
            border: 1px solid #ced4da;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body> <!-- El fondo oscuro viene de css/estilos.css -->

<div class="container main-container py-5">
    
    <!-- Encabezado con Botón Volver -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="main-title mb-0"><i class="fas fa-search me-3"></i>Resultados del Filtro</h1>
            <p class="text-white-50 mb-0">Listado de horarios encontrados</p>
        </div>
        <a href="filtros.php" class="btn btn-outline-light px-4 rounded-pill">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <!-- Tarjeta de Resultados -->
    <div class="card custom-card border-0">
        <div class="card-body p-4">
            
            <div class="table-responsive rounded-3 border">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="py-3 ps-3"><i class="fas fa-barcode me-2"></i>NRC</th>
                            <th class="py-3"><i class="fas fa-book me-2"></i>Materia</th>
                            <th class="py-3"><i class="fas fa-chalkboard-teacher me-2"></i>Profesor</th>
                            <th class="py-3 text-center">Sección</th>
                            <th class="py-3 text-center">Días</th>
                            <th class="py-3 text-center"><i class="fas fa-clock me-2"></i>Hora</th>
                            <th class="py-3 text-center"><i class="fas fa-door-open me-2"></i>Salón</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($stmt) {
                            $hasResults = false;
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                $hasResults = true;
                                echo "<tr>
                                    <td class='ps-3'><span class='badge badge-nrc'>{$row['NRC']}</span></td>
                                    <td class='fw-bold text-primary'>{$row['materia']}</td>
                                    <td class='text-secondary'>{$row['profesor']}</td>
                                    <td class='text-center'><span class='badge bg-light text-dark border'>{$row['secc']}</span></td>
                                    <td class='text-center'>{$row['dias']}</td>
                                    <td class='text-center text-nowrap'>{$row['hora']}</td>
                                    <td class='text-center text-muted small'>{$row['salon']}</td>
                                </tr>";
                            }
                            
                            if (!$hasResults) {
                                echo "<tr>
                                        <td colspan='7' class='text-center py-5'>
                                            <div class='text-muted'>
                                                <i class='fas fa-folder-open fa-3x mb-3 text-secondary opacity-50'></i>
                                                <p class='h5'>No se encontraron resultados</p>
                                                <p class='small'>Intenta ajustar tus filtros de búsqueda.</p>
                                            </div>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr>
                                    <td colspan='7' class='text-center py-4 text-danger'>
                                        <i class='fas fa-exclamation-triangle me-2'></i>Error en la consulta de base de datos.
                                    </td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3 text-end text-muted small">
                <i class="fas fa-info-circle me-1"></i> Mostrando resultados directos de la base de datos.
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>