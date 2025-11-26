<?php
require_once __DIR__ . '/../private/db/database.php';

$errorMsg = "";
$redirectUrl = "";

// 1. LÓGICA DE PROCESAMIENTO
if (!isset($_POST['numMaterias']) || empty($_POST['numMaterias'])) {
    $errorMsg = "No se recibieron datos del formulario. Por favor intenta nuevamente.";
} else {
    $numMaterias = intval($_POST['numMaterias']);
    $materias_nombres = [];

    // Recolectar valores
    for ($i = 1; $i <= $numMaterias; $i++) {
        $field = "materia_{$i}";
        if (!empty($_POST[$field])) {
            $val = trim($_POST[$field]);

            // Lógica de NRC (Mantenemos tu lógica original)
            if (preg_match('/^\d+$/', $val)) {
                $sql = "SELECT Materia FROM Horario WHERE NRC = ?";
                $params = array($val);
                // Usamos @ para suprimir errores visuales directos, los manejamos con lógica
                $stmt = @sqlsrv_query($conn, $sql, $params);
                if ($stmt !== false) {
                    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                    if ($row && !empty($row['Materia'])) {
                        $materias_nombres[] = trim($row['Materia']);
                        continue;
                    }
                }
            }
            // Si no es NRC o falla la búsqueda, guardar el valor directo
            $materias_nombres[] = $val;
        }
    }

    // Limpiar duplicados y vacíos
    $materias_nombres = array_values(array_unique(array_filter($materias_nombres)));

    if (empty($materias_nombres)) {
        $errorMsg = "No se seleccionaron materias válidas. Por favor regresa y selecciona al menos una.";
    } else {
        // ÉXITO: Construir URL
        $materias_url = urlencode(implode(',', $materias_nombres));
        $redirectUrl = '/PROYECTO_ISII/public/filtros_finales.php?materias=' . $materias_url;
    }
}

// Cerrar conexión si existe
if (isset($conn) && function_exists('sqlsrv_close')) {
    @sqlsrv_close($conn);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesando...</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link href="css/estilos.css" rel="stylesheet">

    <style>
        /* Centrado perfecto para mensajes de carga/error */
        .full-height {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .status-card {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
    </style>
</head>
<body> <div class="container full-height">
        
        <?php if (!empty($errorMsg)): ?>
            <div class="card custom-card status-card border-0">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-circle text-danger fa-4x"></i>
                    </div>
                    <h3 class="fw-bold text-danger mb-3">¡Ups! Algo salió mal</h3>
                    <p class="text-muted mb-4"><?php echo htmlspecialchars($errorMsg); ?></p>
                    
                    <a href="javascript:history.back()" class="btn btn-outline-danger rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Regresar e intentar de nuevo
                    </a>
                </div>
            </div>

        <?php else: ?>
            <div class="card custom-card status-card border-0">
                <div class="card-body p-5">
                    <div class="mb-4 text-primary">
                        <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Procesando Materias</h4>
                    <p class="text-muted small mb-0">Te estamos redirigiendo a los filtros...</p>
                </div>
            </div>

            <script>
                setTimeout(function() {
                    window.location.href = "<?php echo $redirectUrl; ?>";
                }, 1500); // 1.5 segundos de retraso para que se vea la animación bonita
            </script>
        <?php endif; ?>

    </div>

</body>
</html>