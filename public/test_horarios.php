<?php
//brian
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug: marca que PHP ejecutó (aparecerá en el código fuente como comentario)
echo "<!-- DEBUG: test_horarios.php cargado -->\n";
//brian
// test_horarios.php
// Este archivo sirve para probar el generador de combinaciones desde el navegador
// También recibe datos filtrados desde filtros_finales.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materias = $_POST['materias'] ?? [];

    // Enviar datos al controlador
    $url = 'http://localhost/proyecto_isi/private/controllers/generar_horarios.php';

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
        .alert { padding: 12px; margin: 15px 0; border-radius: 5px; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>

    <!-- Agregado: html2pdf (html2canvas + jsPDF BRIAN) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
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

    <div id="resultadosDiv"></div>

    <script>
        // Recuperar datos del sessionStorage (enviados desde filtros_finales.php)
        const datosSessionStorage = sessionStorage.getItem('combinacionesData');
        let data = null;

        if (datosSessionStorage) {
            data = JSON.parse(datosSessionStorage);
            sessionStorage.removeItem('combinacionesData'); // Limpiar después de usar
            mostrarResultadosDelFiltro(data);
        }

        function mostrarResultadosDelFiltro(data) {
            const div = document.getElementById('resultadosDiv');
            if (!data || data.status !== 'ok' || !data.combinaciones) {
                div.innerHTML = '<div class="alert alert-info">No hay datos disponibles.</div>';
                return;
            }

            // Botón para exportar todo en un solo PDF BRIAN
            let html = `<div style="margin-bottom:12px;"><button onclick="exportAllToPDF()" style="padding:8px 12px;">Exportar TODO a PDF</button></div>`;

            html += `

                <div class="alert alert-info">
                    <strong>Filtros aplicados:</strong><br>
                    Turno: ${data.filtros_aplicados?.turno || 'todos'} | 
                    Profesor prioridad: ${data.filtros_aplicados?.profesor_prioridad || 'ninguno'} | 
                    Profesor excluido: ${data.filtros_aplicados?.profesor_excluir || 'ninguno'} | 
                    Ordenamiento: ${data.filtros_aplicados?.ordenamiento || 'horas muertas'}
                </div>
            `;
//BRIAN
            data.combinaciones.forEach((comb, i) => {
                const detalle = comb.detalle_horarios || {};
                // cada combinación tiene un contenedor con id para exportar
                html += `
                    <div id="comb-${i}" style="background: #fff; padding: 12px; border-radius: 6px; margin-bottom: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                          <h3 style="margin:0">Combinación #${i + 1}</h3>
                          <div>
                            <button onclick="exportCombinationToPDF(${i})" style="padding:6px 10px;margin-left:8px;">Exportar PDF</button>
                          </div>
                        </div>
                        <p style="margin:6px 0;"><strong>NRCs incluidos:</strong> ${comb.nrcs_incluidos.join(', ')}</p>
                        <p style="margin:6px 0;"><strong>Profesores:</strong> ${(comb.profesores || []).join(', ')}</p>
                        <p style="margin:6px 0;"><strong>Turno:</strong> ${comb.turno || 'mixto'} | <strong>Horas muertas:</strong> ${comb.horas_muertas || 0} hrs</p>
                        ${renderTimetable(detalle)}
                    </div>
                `;
            });

            div.innerHTML = html;
        }
//BRIAN
        function renderTimetable(detalle) {
            // Convertir si es string
            if (typeof detalle === 'string') {
                try {
                    detalle = JSON.parse(detalle);
                } catch (e) {
                    return '<p><em>Sin registros válidos</em></p>';
                }
            }

            // Recolectar todos los registros y días
            const registros = [];
            const daysSet = {};
            
            Object.keys(detalle).forEach(nrc => {
                const info = detalle[nrc];
                const materia = info.materia || '';
                const seccion = info.seccion || '';
                const profesor = info.profesor || '';
                const salon = info.salon || '';
                const regs = info.registros || [];
                
                regs.forEach(r => {
                    const dia = (r.dia || '').toUpperCase().trim();
                    const inicio = r.inicio || '';
                    const fin = r.fin || '';
                    if (!dia || !inicio || !fin) return;
                    
                    registros.push({
                        nrc, materia, seccion, profesor, salon,
                        dia, inicio, fin
                    });
                    daysSet[dia] = true;
                });
            });

            if (registros.length === 0) {
                return '<p><em>Sin registros de horario</em></p>';
            }

            // Ordenar días
            const preferred = ['L', 'A', 'M', 'W', 'J', 'V', 'S', 'D', 'LU', 'MA', 'MI', 'JU', 'VI'];
            let days = Object.keys(daysSet);
            days.sort((a, b) => {
                const pa = preferred.indexOf(a);
                const pb = preferred.indexOf(b);
                return (pa === -1 ? 999 : pa) - (pb === -1 ? 999 : pb);
            });

            // Recolectar puntos de tiempo
            const timePoints = {};
            registros.forEach(r => {
                timePoints[r.inicio] = true;
                timePoints[r.fin] = true;
            });
            
            let times = Object.keys(timePoints).sort((a, b) => {
                const [ah, am] = a.split(':').map(Number);
                const [bh, bm] = b.split(':').map(Number);
                return ah * 60 + am - (bh * 60 + bm);
            });

            // Construir intervalos
            const intervals = [];
            for (let i = 0; i < times.length - 1; i++) {
                intervals.push({ start: times[i], end: times[i + 1] });
            }

            // Mapear registros a intervalos
            const dayIntervalMap = {};
            const startsMap = {};
            
            registros.forEach(r => {
                const startIdx = times.indexOf(r.inicio);
                const endIdx = times.indexOf(r.fin);
                if (startIdx === -1 || endIdx === -1) return;
                
                const span = endIdx - startIdx;
                const dia = r.dia;
                
                if (!dayIntervalMap[dia]) dayIntervalMap[dia] = new Array(intervals.length).fill(null);
                if (!startsMap[dia]) startsMap[dia] = {};
                
                startsMap[dia][startIdx] = { span, data: r };
                
                for (let k = startIdx; k < endIdx; k++) {
                    dayIntervalMap[dia][k] = r;
                }
            });

            // Construir tabla HTML
            let html = '<table style="border-collapse: collapse; margin-top: 16px; width: 100%;">';
            html += '<thead><tr><th style="border: 1px solid #ccc; padding: 6px; width: 90px;">Hora</th>';
            
            days.forEach(d => {
                html += `<th style="border: 1px solid #ccc; padding: 6px;">${d}</th>`;
            });
            
            html += '</tr></thead><tbody>';

            for (let i = 0; i < intervals.length; i++) {
                const int = intervals[i];
                html += `<tr>
                    <td style="border: 1px solid #ccc; padding: 6px; font-size: 12px; background: #f9f9f9;">
                        ${int.start} - ${int.end}
                    </td>`;
                
                days.forEach(d => {
                    if (startsMap[d] && startsMap[d][i]) {
                        const span = Math.max(1, startsMap[d][i].span);
                        const r = startsMap[d][i].data;
                        const content = `<div style="background: #e8f4ff; border-radius: 4px; padding: 4px; font-weight: 600;">
                            ${r.nrc} - ${r.materia}
                        </div>
                        <div style="font-size: 12px; color: #444;">
                            ${r.salon} · ${r.profesor}
                        </div>`;
                        html += `<td rowspan="${span}" style="border: 1px solid #ccc; padding: 6px; vertical-align: top;">
                            ${content}
                        </td>`;
                    } else if (dayIntervalMap[d] && dayIntervalMap[d][i]) {
                        // Cellda ya ocupada por rowspan, no añadir
                    } else {
                        html += `<td style="border: 1px solid #ccc; padding: 6px;"></td>`;
                    }
                });
                
                html += '</tr>';
            }

            html += '</tbody></table>';
            return html;
        }
//BRIAN
        /*
 Reemplaza las funciones de exportación basadas en clonación por estas
 que simplemente ocultan los botones temporalmente en el DOM original,
 generan el PDF y luego restauran la visibilidad. Evita problemas de
 contenido vacío al mantener los estilos y recursos en el mismo nodo.
*/

function _hideButtonsTemp(container) {
    const buttons = Array.from(container.querySelectorAll('button'));
    const backup = buttons.map(b => ({ el: b, display: b.style.display || '' }));
    buttons.forEach(b => b.style.display = 'none');
    return backup;
}

function _restoreButtons(backup) {
    if (!Array.isArray(backup)) return;
    backup.forEach(item => {
        try { item.el.style.display = item.display; } catch (e) { /* ignore */ }
    });
}

function exportCombinationToPDF(i) {
    const el = document.getElementById('comb-' + i);
    if (!el) return alert('Elemento no encontrado para exportar.');
    const backup = _hideButtonsTemp(el);
    const opt = {
        margin:       0.5,
        filename:     `horario_${i+1}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, logging: false },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(el).save()
        .then(() => _restoreButtons(backup))
        .catch(() => _restoreButtons(backup));
}

function exportAllToPDF() {
    const el = document.getElementById('resultadosDiv');
    if (!el) return alert('No hay contenido para exportar.');
    const backup = _hideButtonsTemp(el);
    const opt = {
        margin:       0.4,
        filename:     `horarios_todos.pdf`,
        image:        { type: 'jpeg', quality: 0.95 },
        html2canvas:  { scale: 2, logging: false },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(el).save()
        .then(() => _restoreButtons(backup))
        .catch(() => _restoreButtons(backup));
}
//BRIAN
        // Si hay datos del POST tradicional, mostrarlos también
        <?php if (!empty($data)): ?>
            if (!datosSessionStorage) {
                // Mostrar datos del POST
                document.getElementById('resultadosDiv').innerHTML = '<div class="alert alert-info">Cargados desde formulario tradicional</div>';
            }
        <?php endif; ?>
    </script>
</body>
</html>

