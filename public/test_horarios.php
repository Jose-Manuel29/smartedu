<?php
// test_horarios.php
// Este archivo sirve para probar el generador de combinaciones desde el navegador

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materias = $_POST['materias'] ?? [];

    // Enviar datos al controlador
    $url = 'http://localhost/proyecto_ing/smartedu/private/controllers/generar_horarios.php';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['materias' => $materias]));
    $response = curl_exec($ch);
    curl_close($ch);

    echo "<pre>Respuesta cruda del servidor:\n";
    var_dump($response);
    echo "</pre>";

    $data = json_decode($response, true);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de combinaciones de horarios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f6f6f6; }
        h1 { color: #333; }
        form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); width: 400px; }
        input[type="text"] { width: 90%; padding: 6px; margin-bottom: 10px; }
        button { background: #0066cc; color: white; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0055a5; }
        pre { background: #eee; padding: 10px; border-radius: 6px; overflow-x: auto; }
        table { border-collapse: collapse; margin-top: 20px; width: 100%; background: white; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #ddd; }
    </style>
</head>
<body>
    <h1>🧩 Prueba de combinaciones válidas</h1>

    <form method="post">
        <p>Escribe entre 2 y 6 materias (deben existir en la tabla <b>horarios</b>):</p>
        <input type="text" name="materias[]" placeholder="Ej. Matemáticas I" required>
        <input type="text" name="materias[]" placeholder="Ej. Física I" required>
        <input type="text" name="materias[]" placeholder="Ej. Programación I">
        <input type="text" name="materias[]" placeholder="Opcional...">
        <input type="text" name="materias[]" placeholder="Opcional...">
        <input type="text" name="materias[]" placeholder="Opcional...">
        <button type="submit">Generar combinaciones</button>
    </form>

    <?php if (!empty($data)): ?>
    <h2>Resultado</h2>

    <?php if (isset($data['status']) && $data['status'] === 'ok'): ?>
        <p><b>Materias:</b> <?= implode(', ', $data['materias_solicitadas']) ?></p>
        <p><b>Total combinaciones válidas:</b> <?= $data['total_combinaciones_validas'] ?></p>

        <table>
            <tr><th>#</th><th>NRCs incluidos</th></tr>
            <?php foreach ($data['combinaciones'] as $i => $comb): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= implode(', ', $comb['nrcs_incluidos']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php else: ?>
        <pre><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    <?php endif; ?>

<?php else: ?>
    <p>No se recibió respuesta del servidor.</p>
<?php endif; ?>

</body>
</html>

