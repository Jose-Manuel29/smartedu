<?php
// ================================================
// generar_horarios.php
// ================================================
// Recibe materias (POST o JSON), busca horarios y genera combinaciones válidas
// ================================================

ob_start(); // Captura cualquier salida para evitar conflictos con headers

// Conexión PDO y dependencias
require_once __DIR__ . '/../db/database.php';
require_once __DIR__ . '/../models/materias_model.php';
require_once __DIR__ . '/../algoritmos/combinador.php';

// Obtener conexión (sqlsrv) desde helper
$db = null;
if (function_exists('get_db_connection')) {
    $db = get_db_connection();
}
if (!$db) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'msg' => 'No hay conexión a la base de datos disponible. Revisa private/db/database.php'], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

// Recibir materias seleccionadas
// Soportamos tres fuentes de entrada:
// 1) POST JSON { materias: [...] }
// 2) Form POST con array 'materias' (materias[])
// 3) Form POST con campos individuales materia_1, materia_2, ...
$rawMaterias = null;
$action = null;

// 1) JSON body
$input = file_get_contents('php://input');
$decoded = json_decode($input, true);
if (is_array($decoded) && isset($decoded['materias']) && is_array($decoded['materias'])) {
    $rawMaterias = $decoded['materias'];
}

// Capturar acción especial (ej. get_stats)
if (is_array($decoded) && isset($decoded['action'])) {
    $action = $decoded['action'];
}

// 2) Form array materias[]
if ($rawMaterias === null && isset($_POST['materias']) && is_array($_POST['materias'])) {
    $rawMaterias = $_POST['materias'];
}

// 3) Campos individuales materia_1..n
if ($rawMaterias === null) {
    $cand = [];
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'materia_') === 0) {
            $cand[] = $v;
        }
    }
    if (!empty($cand)) {
        $rawMaterias = $cand;
    }
}

if (!is_array($rawMaterias) || count($rawMaterias) < 2 || count($rawMaterias) > 6) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'msg' => 'Envía entre 2 y 6 materias', 'valor' => $rawMaterias]);
    exit;
}

$materiasSeleccionadas = array_filter(array_map('trim', $rawMaterias));

// ===== ACCIÓN ESPECIAL: get_profesores_por_materia =====
// Retorna profesores agrupados por materia
if ($action === 'get_profesores_por_materia') {
    $rows = fetchHorariosPorMaterias($db, $materiasSeleccionadas);
    if (empty($rows)) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'msg' => 'No se encontraron horarios para esas materias']);
        exit;
    }

    // Agrupar profesores por materia
    $profesoresPorMateria = [];
    foreach ($rows as $row) {
        $materia = trim($row['Materia']);
        $profesor = trim($row['Profesor'] ?? '');
        
        if (!empty($profesor)) {
            if (!isset($profesoresPorMateria[$materia])) {
                $profesoresPorMateria[$materia] = [];
            }
            if (!in_array($profesor, $profesoresPorMateria[$materia])) {
                $profesoresPorMateria[$materia][] = $profesor;
            }
        }
    }

    // Ordenar profesores por materia
    foreach ($profesoresPorMateria as &$profs) {
        sort($profs);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ok',
        'profesores_por_materia' => $profesoresPorMateria
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    ob_end_flush();
    exit;
}

// ===== ACCIÓN ESPECIAL: get_stats =====
// Si la acción es 'get_stats', calcular estadísticas sin aplicar filtros
if ($action === 'get_stats') {
    // Consultar horarios
    $rows = fetchHorariosPorMaterias($db, $materiasSeleccionadas);
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

    // Generar combinaciones válidas (sin filtros)
    $combinacionesValidas = generarCombinacionesValidas($db, $nrcs_por_materia, $por_nrc, null);

    // Extraer profesores únicos
    $profesoresUnicos = [];
    foreach ($por_nrc as $nrc => $detalle) {
        if (!empty($detalle['profesor'])) {
            $prof = trim($detalle['profesor']);
            if (!in_array($prof, $profesoresUnicos)) {
                $profesoresUnicos[] = $prof;
            }
        }
    }
    sort($profesoresUnicos);

    // Enviar estadísticas
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ok',
        'stats' => [
            'total_combinaciones' => count($combinacionesValidas),
            'total_profesores' => count($profesoresUnicos),
            'profesores' => $profesoresUnicos
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    ob_end_flush();
    exit;
}

// ===== FLUJO NORMAL: Generar combinaciones con filtros =====

// Consultar horarios
$rows = fetchHorariosPorMaterias($db, $materiasSeleccionadas);
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
$combinacionesValidas = generarCombinacionesValidas($db, $nrcs_por_materia, $por_nrc, null);

// ======================================================================================
// [FIX IMPORTANTE] PRE-CALCULAR METADATOS PARA QUE LOS FILTROS FUNCIONEN
// Calculamos Turno, Profesores y Horas Muertas ANTES de filtrar
// ======================================================================================
foreach ($combinacionesValidas as &$comb) {
    $detalle = $comb['detalle_horarios'];
    $profesores = [];
    $minInicio = 24 * 60; // 1440
    $maxFin = 0;
    
    // Recorremos cada materia de esta combinación
    foreach ($detalle as $nrcDetalle) {
        // 1. Recolectar Profesores
        if (!empty($nrcDetalle['profesor'])) {
            $p = trim($nrcDetalle['profesor']);
            if (!in_array($p, $profesores)) {
                $profesores[] = $p;
            }
        }
        
        // 2. Calcular rango de horas (para Turno)
        if (isset($nrcDetalle['registros']) && is_array($nrcDetalle['registros'])) {
            foreach ($nrcDetalle['registros'] as $reg) {
                // Usamos inicio_min y fin_min si combinador.php los provee, si no parseamos
                $inicioM = isset($reg['inicio_min']) ? $reg['inicio_min'] : 0;
                $finM = isset($reg['fin_min']) ? $reg['fin_min'] : 0;
                
                // Si no vienen pre-calculados, parsear H:i
                if ($inicioM == 0 && isset($reg['inicio'])) {
                    $parts = explode(':', $reg['inicio']);
                    $inicioM = intval($parts[0])*60 + intval($parts[1]);
                }
                if ($finM == 0 && isset($reg['fin'])) {
                    $parts = explode(':', $reg['fin']);
                    $finM = intval($parts[0])*60 + intval($parts[1]);
                }

                if ($inicioM < $minInicio) $minInicio = $inicioM;
                if ($finM > $maxFin) $maxFin = $finM;
            }
        }
    }
    
    // 3. Determinar Turno
    // Matutino: Termina a las 14:00 (14*60 = 840) o antes
    // Vespertino: Empieza a las 13:00 (13*60 = 780) o después
    // Mixto: Lo demás
    $turno = 'mixto';
    if ($maxFin > 0) {
        if ($maxFin <= 840) { // Hasta 14:00
            $turno = 'matutino';
        } elseif ($minInicio >= 780) { // Desde 13:00
            $turno = 'vespertino';
        }
    }

    // 4. Calcular Horas Muertas (Aproximación para ordenamiento)
    // (Fin del día - Inicio del día) - (Suma de duraciones reales) = Huecos totales
    // Nota: Esto es una simplificación para que el filtro funcione.
    $horasMuertas = 0;
    if ($maxFin > $minInicio) {
        $rangoTotal = ($maxFin - $minInicio) / 60; // en horas
        // Aquí podríamos restar la duración real de clases si quisiéramos exactitud
        // Por ahora, usamos el rango como proxy para ordenamiento
        $horasMuertas = round($rangoTotal, 1); 
    }

    // Guardar en la combinación para que los filtros lo vean
    $comb['turno'] = $turno;
    $comb['profesores'] = $profesores;
    $comb['horas_muertas'] = $horasMuertas;
    $comb['hora_inicio'] = $minInicio;
    $comb['hora_fin'] = $maxFin;
}
unset($comb); // Romper referencia del foreach

// ===== APLICAR FILTROS =====
// Ahora que ya existen 'turno' y 'profesores', los filtros funcionarán

$turno_filtro = isset($decoded['turno']) ? $decoded['turno'] : 'todos';

// Procesar profesor_prioridad: puede ser string o array
$profesor_prioridad = [];
if (isset($decoded['profesor_prioridad'])) {
    if (is_array($decoded['profesor_prioridad'])) {
        $profesor_prioridad = array_filter(array_map('trim', $decoded['profesor_prioridad']));
    } elseif (is_string($decoded['profesor_prioridad'])) {
        $temp = trim($decoded['profesor_prioridad']);
        if (!empty($temp)) {
            $profesor_prioridad = [$temp];
        }
    }
}

// Procesar profesor_excluir: puede ser string o array
$profesor_excluir = [];
if (isset($decoded['profesor_excluir'])) {
    if (is_array($decoded['profesor_excluir'])) {
        $profesor_excluir = array_filter(array_map('trim', $decoded['profesor_excluir']));
    } elseif (is_string($decoded['profesor_excluir'])) {
        $temp = trim($decoded['profesor_excluir']);
        if (!empty($temp)) {
            $profesor_excluir = [$temp];
        }
    }
}

$ordenamiento = isset($decoded['ordenamiento']) ? $decoded['ordenamiento'] : 'horas_muertas';

// 1. Filtrar por turno
if ($turno_filtro !== 'todos') {
    $combinacionesValidas = array_filter($combinacionesValidas, function($comb) use ($turno_filtro) {
        return isset($comb['turno']) && $comb['turno'] === $turno_filtro;
    });
    $combinacionesValidas = array_values($combinacionesValidas); // Re-indexar
}

// 2. Filtrar por profesor a excluir (AND logic: remove combos with ANY excluded professor)
if (!empty($profesor_excluir)) {
    $combinacionesValidas = array_filter($combinacionesValidas, function($comb) use ($profesor_excluir) {
        $profesores = isset($comb['profesores']) ? $comb['profesores'] : [];
        // Retornar true si NINGUNO de los profesores a excluir está en la combinación
        foreach ($profesor_excluir as $p) {
            if (in_array($p, $profesores)) {
                return false; // Excluir esta combinación
            }
        }
        return true; // Mantener esta combinación
    });
    $combinacionesValidas = array_values($combinacionesValidas); // Re-indexar
}

// 3. Filtrar/ordenar por profesor de prioridad (AND logic: keep combos with ALL prioritized professors)
if (!empty($profesor_prioridad)) {
    // Filtrar estrictamente: dejar solo combinaciones que contengan TODOS los profesores de la lista
    $combinacionesValidas = array_filter($combinacionesValidas, function($comb) use ($profesor_prioridad) {
        $profesores = isset($comb['profesores']) ? $comb['profesores'] : [];
        // Retornar true si TODOS los profesores de prioridad están en la combinación
        foreach ($profesor_prioridad as $p) {
            if (!in_array($p, $profesores)) {
                return false; // Excluir si falta algún profesor
            }
        }
        return true; // Mantener esta combinación (tiene TODOS)
    });
    $combinacionesValidas = array_values($combinacionesValidas); // Re-indexar

    // Luego ordenar por horas muertas (mejor dentro del subconjunto)
    usort($combinacionesValidas, function($a, $b) {
        $a_horas = isset($a['horas_muertas']) ? $a['horas_muertas'] : 999;
        $b_horas = isset($b['horas_muertas']) ? $b['horas_muertas'] : 999;
        return $a_horas <=> $b_horas;
    });
}

// 4. Ordenar por preferencia del usuario
if ($ordenamiento === 'horas_muertas') {
    usort($combinacionesValidas, function($a, $b) {
        $a_horas = isset($a['horas_muertas']) ? $a['horas_muertas'] : 999;
        $b_horas = isset($b['horas_muertas']) ? $b['horas_muertas'] : 999;
        return $a_horas <=> $b_horas; // Ascendente: menos horas muertas primero
    });
} elseif ($ordenamiento === 'hora_inicio') {
    usort($combinacionesValidas, function($a, $b) {
        $a_inicio = isset($a['hora_inicio']) ? $a['hora_inicio'] : 999999;
        $b_inicio = isset($b['hora_inicio']) ? $b['hora_inicio'] : 999999;
        return $a_inicio <=> $b_inicio; // Ascendente: más temprana primero
    });
} elseif ($ordenamiento === 'hora_fin') {
    usort($combinacionesValidas, function($a, $b) {
        $a_fin = isset($a['hora_fin']) ? $a['hora_fin'] : 0;
        $b_fin = isset($b['hora_fin']) ? $b['hora_fin'] : 0;
        return $a_fin <=> $b_fin; // Ascendente: más temprana primero
    });
}

// 7️⃣ Preparar salida
$resultado = [];
foreach ($combinacionesValidas as $comb) {
    $resultado[] = [
        'materias_incluidas' => explode(',', $comb['materias_incluidas']),
        'nrcs_incluidos' => explode(',', $comb['nrcs_incluidos']),
        'detalle_horarios' => $comb['detalle_horarios'],
        'turno' => $comb['turno'] ?? 'mixto',
        'horas_muertas' => $comb['horas_muertas'] ?? 0,
        'profesores' => $comb['profesores'] ?? [],
    ];
}

// Enviar JSON final
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => 'ok',
    'materias_solicitadas' => $materiasSeleccionadas,
    'total_combinaciones_validas' => count($resultado),
    'filtros_aplicados' => [
        'turno' => $turno_filtro,
        'profesor_prioridad' => $profesor_prioridad,
        'profesor_excluir' => $profesor_excluir,
        'ordenamiento' => $ordenamiento
    ],
    'combinaciones' => $resultado
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

ob_end_flush();
exit;