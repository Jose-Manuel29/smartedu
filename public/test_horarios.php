<?php
// test_horarios.php
// Este archivo sirve para probar el generador de combinaciones desde el navegador
//ejemplo de uso: abrir en el navegador, ingresar materias y ver resultados.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materias = $_POST['materias'] ?? [];

    // Enviar datos al controlador
    // Ajustado: usar la ruta correcta dentro de este proyecto `smartedu`
    $url = 'http://localhost/smartedu/private/controllers/generar_horarios.php';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['materias' => $materias]));
    $response = curl_exec($ch);
    curl_close($ch);

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

    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['materias'])): ?>
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
    <?php endif; ?>

    <?php if (!empty($data)): ?>
    <h2>Resultado</h2>

    <?php if (isset($data['status']) && $data['status'] === 'ok'): ?>
        <p><b>Materias:</b> <?= implode(', ', $data['materias_solicitadas']) ?></p>
        <p><b>Total combinaciones válidas:</b> <?= $data['total_combinaciones_validas'] ?></p>

        <style>
            .timetable{width:100%;border-collapse:collapse;margin-top:16px}
            .timetable th,.timetable td{border:1px solid #ddd;padding:6px;font-size:13px}
            .combo-card{background:#fff;padding:12px;border-radius:6px;margin-bottom:18px;box-shadow:0 2px 6px rgba(0,0,0,0.05)}
            .slot{background:#e8f4ff;border-radius:4px;padding:4px;font-weight:600}
            .small{font-size:12px;color:#444}
        </style>

        <?php
        // Helper: construir una cuadrícula tipo horario a partir del detalle (array por NRC)
        function render_timetable(array $detalle): string {
            // Recolectar todos los registros y días
            $registros = [];
            $daysSet = [];
            foreach ($detalle as $nrc => $info) {
                $materia = $info['materia'] ?? '';
                $seccion = $info['seccion'] ?? '';
                $profesor = $info['profesor'] ?? '';
                $salon = $info['salon'] ?? '';
                $regs = $info['registros'] ?? [];
                foreach ($regs as $r) {
                    $dia = strtoupper(trim($r['dia'] ?? ''));
                    $inicio = $r['inicio'] ?? '';
                    $fin = $r['fin'] ?? '';
                    if (empty($dia) || empty($inicio) || empty($fin)) continue;
                    $registros[] = [
                        'nrc'=>$nrc,'materia'=>$materia,'seccion'=>$seccion,'profesor'=>$profesor,'salon'=>$salon,
                        'dia'=>$dia,'inicio'=>$inicio,'fin'=>$fin
                    ];
                    $daysSet[$dia] = true;
                }
            }

            if (empty($registros)) {
                return '<div class="small"><em>Sin registros de horario para esta combinación.</em></div>';
            }

            // Ordenar días (mantener orden lógico si vienen como L M W J V S D u otros)
            $preferred = ['L','M','W','J','V','S','D','LU','MA','MI','JU','VI'];
            $days = array_keys($daysSet);
            usort($days, function($a,$b) use ($preferred){
                $pa = array_search($a,$preferred); $pb = array_search($b,$preferred);
                if ($pa === false) $pa = 999; if ($pb === false) $pb = 999;
                return $pa - $pb;
            });

            // Recolectar puntos de tiempo (todos los inicios y fines)
            $timePoints = [];
            foreach ($registros as $r) {
                $timePoints[$r['inicio']] = true;
                $timePoints[$r['fin']] = true;
            }
            $timePoints = array_keys($timePoints);
            usort($timePoints, function($a,$b){
                list($ah,$am)=explode(':',$a); list($bh,$bm)=explode(':',$b);
                return intval($ah)*60+intval($am) - (intval($bh)*60+intval($bm));
            });

            // Construir intervalos entre puntos consecutivos
            $intervals = [];
            for ($i=0;$i<count($timePoints)-1;$i++){
                $intervals[] = ['start'=>$timePoints[$i],'end'=>$timePoints[$i+1]];
            }

            // Mapear registros a índices de intervalos
            $dayIntervalMap = [];// day => intervalIndex => registro info or null
            $startsMap = [];// day => intervalIndex => registro starting here
            foreach ($registros as $r) {
                // encontrar indices
                $startIndex = array_search($r['inicio'],$timePoints);
                $endIndex = array_search($r['fin'],$timePoints);
                if ($startIndex === false || $endIndex === false) continue;
                $span = $endIndex - $startIndex;
                $dia = $r['dia'];
                if (!isset($dayIntervalMap[$dia])) $dayIntervalMap[$dia] = array_fill(0, count($intervals), null);
                $startsMap[$dia][$startIndex] = ['span'=>$span,'data'=>$r];
                // mark filled for convenience
                for ($k=$startIndex;$k<$endIndex;$k++) {
                    $dayIntervalMap[$dia][$k] = $r;
                }
            }

            // Construir HTML
            $html = '<table class="timetable"><thead><tr><th style="width:90px">Hora</th>';
            foreach ($days as $d) {
                $html .= '<th>'.htmlspecialchars($d).'</th>';
            }
            $html .= '</tr></thead><tbody>';

            for ($i=0;$i<count($intervals);$i++){
                $int = $intervals[$i];
                $html .= '<tr>';
                $html .= '<td class="small">'.htmlspecialchars($int['start']).' - '.htmlspecialchars($int['end']).'</td>';
                foreach ($days as $d) {
                    // si hay un registro que comienza aquí en este día, render con rowspan
                    if (isset($startsMap[$d][$i])) {
                        $span = max(1,intval($startsMap[$d][$i]['span']));
                        $r = $startsMap[$d][$i]['data'];
                        $content = '<div class="slot">'.htmlspecialchars($r['nrc']).' - '.htmlspecialchars($r['materia']).'</div>';
                        $meta = '<div class="small">'.htmlspecialchars($r['salon']).' · '.htmlspecialchars($r['profesor']).'</div>';
                        $html .= '<td rowspan="'. $span .'">' . $content . $meta . '</td>';
                    } else {
                        // si está ocupado por un span que empezó antes, saltar (no añadir celda)
                        if (isset($dayIntervalMap[$d][$i]) && $dayIntervalMap[$d][$i] !== null) {
                            // skip cell, already spanned
                        } else {
                            $html .= '<td></td>';
                        }
                    }
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            return $html;
        }
        
        // Mostrar cada combinación como una 'card' con su cuadrícula
        foreach ($data['combinaciones'] as $i => $comb):
            $detalle = $comb['detalle_horarios'] ?? [];
            ?>
            <div class="combo-card">
                <h3>Combinación #<?php echo $i + 1; ?></h3>
                <p><strong>NRCs incluidos:</strong> <?php echo htmlspecialchars(implode(', ', $comb['nrcs_incluidos'] ?? [])); ?></p>
                <?php echo render_timetable($detalle); ?>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <pre><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    <?php endif; ?>

<?php else: ?>
    <p>No se recibió respuesta del servidor.</p>
<?php endif; ?>

</body>
</html>

