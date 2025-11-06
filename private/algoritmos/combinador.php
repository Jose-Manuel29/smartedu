<?php
// combinador.php

function agruparPorNrcYPorMateria($rows) {
    $por_nrc = [];
    $nrcs_por_materia = [];

    foreach ($rows as $row) {
        $materia = trim($row['Materia']);
        $nrc = trim($row['NRC']);

        //  CONVERSIÓN: Parsear Dias y Hora a estructura de registros
        $registros = parsearHorario($row);

        // Inicializar estructura si no existe
        if (!isset($por_nrc[$nrc])) {
            $por_nrc[$nrc] = [
                'nrc' => $nrc,
                'materia' => $materia,
                'clave' => $row['Clave'] ?? '',
                'seccion' => $row['Secc'] ?? '',
                'profesor' => $row['Profesor'] ?? '',
                'salon' => $row['Salon'] ?? '',
                'registros' => []
            ];
        }

        // Agregar los registros parseados
        $por_nrc[$nrc]['registros'] = array_merge(
            $por_nrc[$nrc]['registros'], 
            $registros
        );

        // Asociar NRCs a su materia
        if (!isset($nrcs_por_materia[$materia])) {
            $nrcs_por_materia[$materia] = [];
        }
        if (!in_array($nrc, $nrcs_por_materia[$materia])) {
            $nrcs_por_materia[$materia][] = $nrc;
        }
    }

    error_log("Función agruparPorNrcYPorMateria ejecutada");
    error_log("Materias detectadas: " . count($nrcs_por_materia));
    error_log("Total NRCs únicos: " . count($por_nrc));

    return [
        'por_nrc' => $por_nrc,
        'nrcs_por_materia' => $nrcs_por_materia
    ];
}

/**
 * Parsea el campo Dias y Hora de la BD a estructura de registros
 * 
 * @param array $row Fila de la BD con campos 'Dias' y 'Hora'
 * @return array Array de registros con 'dia', 'inicio', 'fin'
 * 
 * Ejemplos:
 * Dias: "L,W,V"  Hora: "07:00-09:00"
 * Dias: "M,J"    Hora: "14:00-16:00"
 */
function parsearHorario(array $row): array {
    $registros = [];
    
    $dias = isset($row['Dias']) ? trim($row['Dias']) : '';
    $hora = isset($row['Hora']) ? trim($row['Hora']) : '';

    // Validar que existan datos
    if (empty($dias) || empty($hora)) {
        error_log("⚠️ Fila sin dias u hora válidos: NRC={$row['NRC']}");
        return [];
    }

    // Separar días (pueden venir como "L,W,V" o "L, W, V")
    $arrayDias = array_map('trim', explode(',', $dias));

    // Separar hora inicio y fin (formato: "07:00-09:00")
    $partes = explode('-', $hora);
    if (count($partes) !== 2) {
        error_log("⚠️ Formato de hora inválido: '{$hora}' en NRC={$row['NRC']}");
        return [];
    }

    $inicio = trim($partes[0]);
    $fin = trim($partes[1]);

    // Validar formato de hora (HH:MM)
    if (!preg_match('/^\d{2}:\d{2}$/', $inicio) || !preg_match('/^\d{2}:\d{2}$/', $fin)) {
        error_log("⚠️ Formato de hora inválido: '{$hora}' en NRC={$row['NRC']}");
        return [];
    }

    // Crear un registro por cada día
    foreach ($arrayDias as $dia) {
        $dia = strtoupper($dia); // Normalizar a mayúsculas
        $registros[] = [
            'dia' => $dia,
            'inicio' => $inicio,
            'fin' => $fin
        ];
    }

    return $registros;
}

function registrosSeTraslapan(array $a, array $b): bool {
    if ($a['dia'] !== $b['dia']) return false;
    return ($a['inicio'] < $b['fin']) && ($b['inicio'] < $a['fin']);
}

function validarCombinacionDetalle(array $combDetalle): bool {
    $nrcs = array_keys($combDetalle);
    $count = count($nrcs);

    for ($i = 0; $i < $count; $i++) {
        $nrcA = $nrcs[$i];
        
        // ✅ Verificar que exista 'registros'
        if (!isset($combDetalle[$nrcA]['registros'])) {
            error_log("⚠️ NRC {$nrcA} no tiene campo 'registros'");
            continue;
        }

        foreach ($combDetalle[$nrcA]['registros'] as $regA) {
            for ($j = $i + 1; $j < $count; $j++) {
                $nrcB = $nrcs[$j];
                
                // ✅ Verificar que exista 'registros'
                if (!isset($combDetalle[$nrcB]['registros'])) {
                    error_log("⚠️ NRC {$nrcB} no tiene campo 'registros'");
                    continue;
                }

                foreach ($combDetalle[$nrcB]['registros'] as $regB) {
                    if (registrosSeTraslapan($regA, $regB)) {
                        return false;
                    }
                }
            }
        }
    }
    return true;
}

function generarCombinacionesValidas(PDO $pdo, array $nrcs_por_materia, array $por_nrc, ?callable $onValid = null): array {
    $materias = array_keys($nrcs_por_materia);
    $numMaterias = count($materias);
    $resultados = [];
    $seleccion = [];

    $insertCb = function(array $seleccionActual) use (&$por_nrc, $pdo, $onValid, &$resultados) {
        $detalle = [];
        $materiasIncl = [];
        $nrcsIncl = [];

        foreach ($seleccionActual as $materia => $nrc) {
            $detalle[$nrc] = $por_nrc[$nrc];
            $materiasIncl[] = $materia;
            $nrcsIncl[] = $nrc;
        }

        if (!validarCombinacionDetalle($detalle)) {
            return;
        }

        $materias_incluidas_str = implode(',', $materiasIncl);
        $nrcs_incluidos_str = implode(',', $nrcsIncl);
        $detalle_horarios_json = json_encode($detalle, JSON_UNESCAPED_UNICODE);

        if ($onValid) {
            $onValid($materias_incluidas_str, $nrcs_incluidos_str, $detalle_horarios_json);
        } else {
            $resultados[] = [
                'materias_incluidas' => $materias_incluidas_str,
                'nrcs_incluidos' => $nrcs_incluidos_str,
                'detalle_horarios' => $detalle
            ];
        }
    };

    $dfs = function(int $idx) use (&$dfs, &$materias, $numMaterias, &$nrcs_por_materia, &$seleccion, &$por_nrc, $insertCb) {
        if ($idx === $numMaterias) {
            $insertCb($seleccion);
            return;
        }
        $materia = $materias[$idx];
        $opcionesNrc = $nrcs_por_materia[$materia];
        if (empty($opcionesNrc)) {
            return;
        }
        foreach ($opcionesNrc as $nrc) {
            $seleccion[$materia] = $nrc;
            $hayChoque = false;
            
            // ✅ Verificar que existan 'registros' antes de comparar
            if (!isset($por_nrc[$nrc]['registros'])) {
                unset($seleccion[$materia]);
                continue;
            }

            foreach ($seleccion as $m => $n) {
                if ($n === $nrc) continue;
                
                if (!isset($por_nrc[$n]['registros'])) {
                    continue;
                }

                foreach ($por_nrc[$nrc]['registros'] as $regA) {
                    foreach ($por_nrc[$n]['registros'] as $regB) {
                        if (registrosSeTraslapan($regA, $regB)) {
                            $hayChoque = true;
                            break 3;
                        }
                    }
                }
            }

            if (!$hayChoque) {
                $dfs($idx + 1);
            }
            unset($seleccion[$materia]);
        }
    };

    $dfs(0);
    
    error_log("✅ Combinaciones válidas generadas: " . count($resultados));
    
    return $resultados;
}