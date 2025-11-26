<?php 
require_once __DIR__ . '/../private/db/database.php'; 

// Preparamos las opciones de materias AQUÍ, antes del HTML.
// Esto evita mezclar lógica pesada dentro del JavaScript.
$options_materias = "";
if (isset($conn) && $conn) {
    $sql = "SELECT DISTINCT m.nombre AS Materia FROM Materia m ORDER BY m.nombre ASC";
    $result = @sqlsrv_query($conn, $sql);
    
    if ($result !== false) {
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $nombre = $row['Materia'];
            // Escapamos comillas para evitar romper el HTML/JS
            $safe_nombre = htmlspecialchars($nombre, ENT_QUOTES);
            $options_materias .= "<option value='" . $safe_nombre . "'>" . $safe_nombre . "</option>";
        }
    } else {
        $options_materias = "<option value=''>-- Error al cargar materias --</option>";
    }
} else {
    $options_materias = "<option value=''>-- Error de conexión --</option>";
}

// Limpiamos saltos de línea para que no rompan la variable de JavaScript
$js_options_materias = str_replace(["\r", "\n"], "", $options_materias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seleccionar Materias</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  
  <link href="css/estilos.css" rel="stylesheet">

  <style>
      /* Ajustes específicos para títulos en fondo oscuro */
      h1.main-title {
          color: #ffffff;
          font-weight: 700;
          text-shadow: 0 4px 10px rgba(0,0,0,0.3);
      }
      .input-group-text {
          background-color: #f8f9fa;
          border-right: none;
      }
      .form-select {
          border-left: none;
      }
      /* Foco en el input group para que parezca un solo elemento */
      .input-group:focus-within {
          box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
          border-radius: 0.375rem;
      }
      .input-group:focus-within .form-select, 
      .input-group:focus-within .input-group-text {
          border-color: #86b7fe;
      }
  </style>
</head>

<body> <div class="container main-container py-5">

  <div class="text-center mb-5">
      <h1 class="main-title"><i class="fas fa-graduation-cap me-3"></i>Selección de Materias</h1>
      <p class="text-muted" style="color: #cbd5e1 !important;">Elige manualmente las materias que deseas cursar</p>
  </div>

  <div class="card custom-card border-0">
    <div class="card-body p-4 p-md-5">
        
        <form id="formMaterias" method="POST" action="guardar_materias.php">

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary">
                    <i class="fas fa-sort-numeric-up-alt me-2"></i>Número de materias a agregar:
                </label>
                <select id="numMaterias" class="form-select form-select-lg" name="numMaterias" required>
                    <option value="">-- Selecciona cantidad --</option>
                    <?php 
                    for ($i = 1; $i <= 8; $i++) {
                        echo "<option value='{$i}'>{$i}</option>";
                    }
                    ?>
                </select>
            </div>

            <hr class="my-4 opacity-25">

            <div id="contenedorMaterias" class="row g-3">
                </div>

            <div class="d-grid gap-2 mt-5">
                <button type="submit" class="btn btn-primary btn-custom-primary btn-lg shadow-sm">
                    <i class="fas fa-save me-2"></i>Guardar Selección
                </button>
                <a href="index.php" class="btn btn-outline-secondary border-0">
                    Cancelar y volver
                </a>
            </div>

        </form>
    </div>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Guardamos las opciones de PHP en una constante de JS para usarlas limpiamente
const OPTIONS_MATERIAS = `<?php echo $js_options_materias; ?>`;

$(document).ready(function(){
  $('#numMaterias').on('change', function(){
    let cantidad = $(this).val();
    let contenedor = $('#contenedorMaterias');
    
    // Animación suave al limpiar
    contenedor.fadeOut(200, function() {
        contenedor.empty(); 
        
        if(cantidad > 0){
            for(let i = 1; i <= cantidad; i++){
                // Usamos Backticks (`) para template strings limpias
                // Agregamos el diseño de Input Group con Icono
                contenedor.append(`
                  <div class="col-md-12 mb-3">
                    <label class="form-label small text-muted fw-bold text-uppercase">Materia ${i}</label>
                    <div class="input-group">
                        <span class="input-group-text text-muted"><i class="fas fa-book"></i></span>
                        <select name="materia_${i}" class="form-select" required>
                            <option value="">-- Selecciona Materia --</option>
                            ${OPTIONS_MATERIAS}
                        </select>
                    </div>
                  </div>
                `);
            }
        }
        // Mostrar con efecto fade in
        contenedor.fadeIn(300);
    });
  });
});
</script>

</body>
</html>