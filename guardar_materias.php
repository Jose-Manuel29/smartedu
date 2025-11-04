<?php
include 'conexion.php';

// Verificar que se recibieron datos válidos
if (!isset($_POST['numMaterias']) || empty($_POST['numMaterias'])) {
    die("<p style='color:red; font-family:Arial;'>⚠️ No se recibieron datos.</p>");
}

$numMaterias = intval($_POST['numMaterias']);
$session_id = uniqid(); // Identificador único de selección

// Insertar cada NRC seleccionado
for ($i = 1; $i <= $numMaterias; $i++) {
    if (!empty($_POST["materia_$i"])) {
        $nrc = $_POST["materia_$i"];

        $sql = "INSERT INTO SeleccionMateria (session_id, NRC) VALUES (?, ?)";
        $params = array($session_id, $nrc);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }
}

// Confirmación
echo "
<div style='padding:30px; max-width:500px; margin:50px auto; font-family:Arial; text-align:center;'>
  <h3>Selección guardada correctamente</h3>
  <p>Los NRCs fueron registrados exitosamente en la base de datos.</p>
  <a href='materias_dinamicas.php' class='btn btn-primary mt-3'>Volver</a>
</div>
";

sqlsrv_close($conn);
?>
