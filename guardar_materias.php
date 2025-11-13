<?php
include 'conexion.php';

// Verificar que se recibieron datos válidos
if (!isset($_POST['numMaterias']) || empty($_POST['numMaterias'])) {
    die("<p style='color:red; font-family:Arial;'>⚠️ No se recibieron datos.</p>");
}

$numMaterias = intval($_POST['numMaterias']);

// Recolectar los valores enviados (esperamos nombres de materia como value).
// Si el formulario todavía envía NRCs, intentamos mapearlos a nombre.
$materias_nombres = [];
for ($i = 1; $i <= $numMaterias; $i++) {
    $field = "materia_{$i}";
    if (!empty($_POST[$field])) {
        $val = trim($_POST[$field]);

        // Si parece un NRC (todo dígitos), intentamos mapear a nombre
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
            // si no se puede mapear, ignorar y seguir
        }

        // Si no es un NRC, lo tratamos como nombre directamente
        $materias_nombres[] = $val;
    }
}

$materias_nombres = array_values(array_unique(array_filter($materias_nombres)));

if (empty($materias_nombres)) {
    die("<p style='color:red; font-family:Arial;'>⚠️ No se obtuvieron nombres de materia válidos.</p>");
}

// En lugar de llamar al generador desde el servidor, redirigimos al usuario
// mediante un POST automático a `public/test_horarios.php` para que la UI
// muestre las combinaciones directamente en el navegador.

// Ajustar target a la ruta correcta dentro del proyecto `smartedu`
$target = 'public/test_horarios.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Redirigiendo…</title>
    <style>body{font-family:Arial;padding:30px;background:#f6f6f6}</style>
    <script>
        function submitForm(){
            document.getElementById('forwardForm').submit();
        }
        window.addEventListener('DOMContentLoaded', submitForm);
    </script>
</head>
<body>
    <div style="max-width:800px;margin:40px auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);">
        <h3>Redirigiendo al generador…</h3>
        <p>Si no se redirige automáticamente, pulsa el botón.</p>

        <form id="forwardForm" method="post" action="<?= htmlspecialchars($target) ?>">
            <?php foreach ($materias_nombres as $m): ?>
                <input type="hidden" name="materias[]" value="<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
            <noscript>
                <button type="submit">Continuar</button>
            </noscript>
        </form>
    </div>
</body>
</html>

<?php
// cerrar conexion sqlsrv si existe
if (function_exists('sqlsrv_close')) {
        @sqlsrv_close($conn);
}
?>
