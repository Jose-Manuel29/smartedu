<?php
// ================================================
// generar_horarios.php
// ================================================
// Recibe materias (POST o JSON), busca horarios y genera combinaciones válidas
// ================================================

ob_start(); // Captura cualquier salida para evitar conflictos con headers

// Conexión PDO y dependencias
require_once __DIR__ . '/../db/conexion.php';
require_once __DIR__ . '/../models/materias_model.php';
require_once __DIR__ . '/../algoritmos/combinador.php';

// Recibir materias seleccionadas
$rawMaterias = $_POST['materias'] ?? null;
if ($rawMaterias === null) {
    $input = file_get_contents('php://input');
    $decoded = json_decode($input, true);
    if (isset($decoded['materias']) && is_array($decoded['materias'])) {
        $rawMaterias = $decoded['materias'];
    }
}

if (!is_array($rawMaterias) || count($rawMaterias) < 2 || count($rawMaterias) > 6) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'msg' => 'Envía entre 2 y 6 materias', 'valor' => $rawMaterias]);
    exit;
}

$materiasSeleccionadas = array_filter(array_map('trim', $rawMaterias));

// Consultar horarios
$rows = fetchHorariosPorMaterias($pdo, $materiasSeleccionadas);
if (empty($rows)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'msg' => 'No se encontraron horarios para esas materias']);
    exit;
}

// Agrupar por NRC y materia
$grupos = agruparPorNrcYPorMateria($rows);
$por_nrc = $grupos['por_nrc'];
$nrcs_por_materia = $grupos['nrcs_por_materia'];

// Verificar que todas tengan grupos disponibles
foreach ($materiasSeleccionadas as $m) {
    if (!isset($nrcs_por_materia[$m]) || empty($nrcs_por_materia[$m])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => "No se encontraron NRCs para la materia $m"]);
        exit;
    }
}

// Generar combinaciones válidas
$combinacionesValidas = generarCombinacionesValidas($pdo, $nrcs_por_materia, $por_nrc, null);

// 7️⃣ Preparar salida
$resultado = [];
foreach ($combinacionesValidas as $comb) {
    $resultado[] = [
        'materias_incluidas' => explode(',', $comb['materias_incluidas']),
        'nrcs_incluidos' => explode(',', $comb['nrcs_incluidos']),
        'detalle_horarios' => $comb['detalle_horarios'],
    ];
}

// Enviar JSON final
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => 'ok',
    'materias_solicitadas' => $materiasSeleccionadas,
    'total_combinaciones_validas' => count($resultado),
    'combinaciones' => $resultado
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

ob_end_flush();
exit;
