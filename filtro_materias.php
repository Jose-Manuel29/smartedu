<?php include 'conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Seleccionar Materias</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">
<div class="container mt-5">
  <h3 class="text-center mb-4">🎓 Selección de Materias (por NRC)</h3>

  <form id="formMaterias" method="POST" action="guardar_materias.php" class="card p-4 shadow-lg rounded-4">

    <!-- Select principal -->
    <div class="mb-4">
      <label class="form-label">Número de materias que deseas agregar:</label>
      <select id="numMaterias" class="form-select" name="numMaterias" required>
        <option value="">-- Selecciona cantidad --</option>
        <?php 
        for ($i = 1; $i <= 6; $i++) {
            echo "<option value='{$i}'>{$i}</option>";
        }
        ?>
      </select>
    </div>

    <!-- Contenedor donde se generarán los selects dinámicamente -->
    <div id="contenedorMaterias"></div>

    <button type="submit" class="btn btn-primary w-100 mt-4">Guardar selección</button>
  </form>
</div>

<script>
$(document).ready(function(){
  $('#numMaterias').on('change', function(){
    let cantidad = $(this).val();
    let contenedor = $('#contenedorMaterias');
    contenedor.empty(); // limpiar anteriores

    if(cantidad > 0){
      for(let i = 1; i <= cantidad; i++){
        contenedor.append(`
          <div class="mb-3">
            <label class="form-label">Materia ${i}</label>
            <select name="materia_${i}" class="form-select" required>
              <option value="">-- Selecciona Materia --</option>
              <?php
              // Obtener los nombres distintos de las materias (solo el nombre)
              $sql = "SELECT DISTINCT m.nombre AS Materia
                      FROM Materia m
                      ORDER BY m.nombre ASC";
              $result = @sqlsrv_query($conn, $sql);
              $options = "";
              if ($result !== false) {
                  while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                      $nombre = $row['Materia'];
                      // Mostrar únicamente el nombre de la materia como texto y value
                      $options .= "<option value='" . htmlspecialchars($nombre, ENT_QUOTES) . "'>" . htmlspecialchars($nombre) . "</option>";
                  }
              } else {
                  $options = "<option value=''>-- No hay materias --</option>";
              }
              echo str_replace("\n", "", $options);
              ?>
            </select>
          </div>
        `);
      }
    }
  });
});
</script>
</body>
</html>
