<?php
require_once __DIR__ . '/../private/db/database.php';

// Verificar que se recibieron datos válidos
if (!isset($_POST['numMaterias']) || empty($_POST['numMaterias'])) {
    die("<p style='color:red; font-family:Arial;'>⚠️ No se recibieron datos.</p>");
}

$numMaterias = intval($_POST['numMaterias']);

// Recolectar los valores enviados
$materias_nombres = [];
for ($i = 1; $i <= $numMaterias; $i++) {
    $field = "materia_{$i}";
    if (!empty($_POST[$field])) {
        $val = trim($_POST[$field]);

        // Si parece un NRC, intentar mapear a nombre de materia (Lógica de tu proyecto original)
        if (preg_match('/^\d+$/', $val)) {
            $sql = "SELECT Materia FROM Horario WHERE NRC = ?";
            $params = array($val);
            $stmt = @sqlsrv_query($conn, $sql, $params);
            if ($stmt !== false) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($row && !empty($row['Materia'])) {
                    $materias_nombres[] = trim($row['Materia']);
                    continue;
                }
            }
        }

        // Si no es un NRC válido o mapeable, lo tratamos como nombre directamente
        $materias_nombres[] = $val;
    }
}

$materias_nombres = array_values(array_unique(array_filter($materias_nombres)));

if (empty($materias_nombres)) {
    die("<p style='color:red; font-family:Arial;'>⚠️ No se obtuvieron nombres de materia válidos.</p>");
}

// 🔑 CAMBIO CLAVE: Redirección GET a filtros_finales.php
$materias_url = urlencode(implode(',', $materias_nombres));
$target = '/PROYECTO_ISII/public/filtros_finales.php?materias=' . $materias_url;

// Redirigir al usuario
header("Location: " . $target);
exit;

// El código HTML de auto-POST ya no es necesario, pero lo dejo aquí comentado por si acaso
/*
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Redirigiendo…</title>
    <style>body{font-family:Arial;padding:30px;background:#f6f6f6}</style>
    <script>
        function submitForm(){
            window.location.href = "<?= htmlspecialchars($target) ?>";
        }
        window.addEventListener('DOMContentLoaded', submitForm);
    </script>
</head>
<body>
    <div style="max-width:800px;margin:40px auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);">
        <h3>Redirigiendo al generador…</h3>
        <p>Si no se redirige automáticamente, pulsa el botón.</p>
    </div>
</body>
</html>
*/

// cerrar conexion sqlsrv si existe
if (function_exists('sqlsrv_close')) {
        @sqlsrv_close($conn);
}
?>