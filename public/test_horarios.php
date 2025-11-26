<?php
//brian
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- DEBUG: test_horarios.php cargado -->\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materias = $_POST['materias'] ?? [];
    $url = 'http://localhost/PROYECTO_ISII/private/controllers/generar_horarios.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados - Combinaciones</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- TU HOJA DE ESTILOS -->
    <link href="css/estilos.css" rel="stylesheet"> 

    <!-- Librería PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>

    <style>
        /* Estilos de Tabla */
        .horario-table th {
            background-color: #f8f9fa;
            color: #495057;
            text-align: center;
            font-size: 0.85rem;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .horario-table td {
            vertical-align: top;
            font-size: 0.9rem;
            height: 60px;
        }
        
        /* Bloques de materia */
        .materia-block {
            border-left: 4px solid #0d6efd;
            background-color: #e8f4ff;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        /* Título blanco para fondo oscuro */
        h1.main-title {
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        /* =========================================
           ESTILOS CRÍTICOS PARA EL PDF 
           ========================================= */
        
        /* Elemento invisible que fuerza el salto de página */
        .saltopagina {
            display: block;
            page-break-before: always !important; /* Fuerza inicio en hoja nueva */
            height: 1px;
            margin: 0;
            border: none;
            visibility: hidden;
            clear: both;
        }

        /* Modo PDF: Limpia el diseño para impresión */
        .pdf-mode {
            background-color: #ffffff !important;
            background-image: none !important;
            color: #000000 !important;
            padding: 20px !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: none !important;
        }
        
        .pdf-mode .card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            margin-bottom: 0 !important;
            page-break-inside: avoid !important; /* Evita que la tabla se parta */
        }

        /* Ocultar elementos innecesarios en el PDF global */
        .pdf-mode .main-title, 
        .pdf-mode .text-muted,
        .pdf-mode #btnExportarGlobal {
            display: none !important;
        }
        
        #contenido-a-exportar {
            display: block !important;
        }
    </style>
</head>
<body> 
    
    <div class="container main-container py-5" id="mainContainer">
        
        <!-- Header -->
        <div class="text-center mb-5" id="headerSection">
            <h1 class="main-title"><i class="fas fa-calendar-alt me-3"></i>Resultados de Horarios</h1>
            <p class="text-muted" style="color: #cbd5e1 !important;">Explora y descarga tus combinaciones ideales</p>
        </div>

        <!-- Contenedor de Resultados -->
        <div id="resultadosDiv">
            <div class="text-center text-white">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Esperando datos...</p>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const datosSessionStorage = sessionStorage.getItem('combinacionesData');
        let data = null;

        if (datosSessionStorage) {
            data = JSON.parse(datosSessionStorage);
            mostrarResultadosDelFiltro(data);
        } else {
             document.getElementById('resultadosDiv').innerHTML = `
                <div class="alert alert-warning text-center shadow-sm border-0 rounded-4 p-4">
                    <h4 class="alert-heading"><i class="fas fa-exclamation-circle me-2"></i>No hay datos</h4>
                    <p>No se encontraron horarios para mostrar.</p>
                    <a href="filtros_finales.php" class="btn btn-warning mt-2 fw-bold">Volver a filtros</a>
                </div>`;
        }

        function mostrarResultadosDelFiltro(data) {
            const div = document.getElementById('resultadosDiv');
            if (!data || data.status !== 'ok' || !data.combinaciones) {
                div.innerHTML = '<div class="alert alert-info">No hay combinaciones disponibles.</div>';
                return;
            }

            // Botón Exportar TODO
            let html = `
                <div class="d-flex justify-content-end mb-4" id="btnExportarGlobal">
                    <button onclick="exportAllToPDF()" class="btn btn-warning btn-custom-warning shadow-lg fw-bold px-4 rounded-pill">
                        <i class="fas fa-file-pdf me-2"></i>Exportar TODO a PDF
                    </button>
                </div>
                <!-- Contenedor Wrapper específico para el PDF -->
                <div id="contenido-a-exportar">
            `;

            data.combinaciones.forEach((comb, i) => {
                const detalle = comb.detalle_horarios || {};
                
                // LÓGICA DE CORTE DE HOJA:
                // Si NO es el primero (i > 0), insertamos el div de corte ANTES de la tarjeta.
                if (i > 0) {
                    html += '<div class="saltopagina"></div>';
                }

                // Tarjeta del Horario
                html += `
                    <div id="comb-${i}" class="card custom-card mb-4 border-0 shadow-sm horario-card">
                        <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h4 class="text-primary fw-bold m-0"><i class="fas fa-layer-group me-2"></i>Opción ${i + 1}</h4>
                            </div>
                            <div class="btn-individual">
                                <button onclick="exportCombinationToPDF(${i})" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold">
                                    <i class="fas fa-download me-1"></i> PDF Individual
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body p-4 pt-2">
                            <!-- Resumen -->
                            <div class="mb-3 p-3 bg-light rounded-3 border">
                                <div class="mb-2">
                                    <span class="badge bg-primary me-2">NRCs</span> 
                                    <span class="text-secondary small fw-bold">${comb.nrcs_incluidos.join(', ')}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="badge bg-info text-dark me-2">Profesores</span>
                                    <span class="text-secondary small">${(comb.profesores || []).join(', ')}</span>
                                </div>
                                <div>
                                    <span class="badge bg-secondary me-2">Detalles</span>
                                    <span class="fw-bold text-dark small">Turno: ${comb.turno || 'mixto'}</span> 
                                    <span class="mx-2 text-muted">|</span>
                                    <span class="fw-bold text-danger small">Horas Muertas: ${comb.horas_muertas || 0} hrs</span>
                                </div>
                            </div>

                            <div class="table-responsive">
                                ${renderTimetable(detalle)}
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>'; // Cierre contenido-a-exportar
            div.innerHTML = html;
        }

        function renderTimetable(detalle) {
            if (typeof detalle === 'string') {
                try { detalle = JSON.parse(detalle); } catch (e) { return '<p>Error datos</p>'; }
            }
            const registros = [];
            const daysSet = {};
            Object.keys(detalle).forEach(nrc => {
                const info = detalle[nrc];
                (info.registros || []).forEach(r => {
                    const dia = (r.dia || '').toUpperCase().trim();
                    if (!dia) return;
                    registros.push({ nrc, materia: info.materia, profesor: info.profesor, salon: info.salon, dia, inicio: r.inicio, fin: r.fin });
                    daysSet[dia] = true;
                });
            });

            if (registros.length === 0) return '<p class="text-muted">Sin registros</p>';

            const preferred = ['L', 'A', 'M', 'W', 'J', 'V', 'S', 'D'];
            let days = Object.keys(daysSet).sort((a, b) => {
                return (preferred.indexOf(a) === -1 ? 999 : preferred.indexOf(a)) - (preferred.indexOf(b) === -1 ? 999 : preferred.indexOf(b));
            });

            const toMinutes = s => { const p = s.split(':').map(Number); return p[0]*60 + p[1]; };
            const pad = n => (n<10?'0':'')+n;
            const timePoints = {};
            registros.forEach(r => { timePoints[r.inicio]=true; timePoints[r.fin]=true; });
            let times = Object.keys(timePoints).map(toMinutes).sort((a,b)=>a-b);
            
            const intervals = [];
            for(let i=0; i<times.length-1; i++) {
                if (times[i+1] - times[i] > 1) intervals.push({ start: times[i], end: times[i+1] });
            }

            let html = '<table class="table table-bordered table-sm mt-3 horario-table shadow-sm">';
            html += '<thead><tr><th style="width: 100px;">HORA</th>';
            days.forEach(d => html += `<th>${d}</th>`);
            html += '</tr></thead><tbody>';

            intervals.forEach(int => {
                const startStr = pad(Math.floor(int.start/60))+':'+pad(int.start%60);
                const endStr = pad(Math.floor(int.end/60))+':'+pad(int.end%60);
                html += `<tr><td class="text-center fw-bold text-secondary bg-light" style="vertical-align: middle;">${startStr}<br><span class="small text-muted">a</span><br>${endStr}</td>`;
                days.forEach(d => {
                    const match = registros.find(r => r.dia === d && toMinutes(r.inicio) <= int.start && toMinutes(r.fin) >= int.end);
                    if (match) {
                        if (toMinutes(match.inicio) === int.start) {
                            let span = 0;
                            for(let k=0; k<intervals.length; k++) {
                                if (intervals[k].start >= toMinutes(match.inicio) && intervals[k].end <= toMinutes(match.fin)) span++;
                            }
                            html += `<td rowspan="${span}" class="p-1">
                                <div class="materia-block">
                                    <div class="fw-bold text-primary small">${match.materia}</div>
                                    <div class="small text-muted"><i class="fas fa-door-open me-1"></i>${match.salon}</div>
                                    <div class="small text-dark"><i class="fas fa-chalkboard-teacher me-1"></i>${match.profesor}</div>
                                    <div class="badge bg-white text-secondary border mt-1">${match.nrc}</div>
                                </div>
                            </td>`;
                        }
                    } else {
                        const isOccupied = registros.some(r => r.dia === d && toMinutes(r.inicio) < int.start && toMinutes(r.fin) > int.start);
                        if (!isOccupied) html += '<td></td>';
                    }
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
            return html;
        }

        // --- FUNCIONES DE EXPORTACIÓN ---

        // Limpia el estilo visual para el PDF
        function prepararVistaParaPDF(activo) {
            const body = document.body;
            const container = document.getElementById('mainContainer');
            const header = document.getElementById('headerSection');
            const btnGlobal = document.getElementById('btnExportarGlobal');
            const btnsIndividuales = document.querySelectorAll('.btn-individual');

            if (activo) {
                body.classList.add('pdf-mode'); 
                container.classList.add('pdf-mode');
                if(header) header.style.display = 'none';
                if(btnGlobal) btnGlobal.style.display = 'none';
                btnsIndividuales.forEach(b => b.style.display = 'none');
            } else {
                body.classList.remove('pdf-mode');
                container.classList.remove('pdf-mode');
                if(header) header.style.display = 'block';
                if(btnGlobal) btnGlobal.style.display = 'flex';
                btnsIndividuales.forEach(b => b.style.display = 'block');
            }
        }

        function exportAllToPDF() {
            const element = document.getElementById('contenido-a-exportar');
            if (!element) return;

            prepararVistaParaPDF(true);

            const opt = {
                margin:       0.4,
                filename:     'horarios_completos.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true, 
                    logging: false,
                    windowWidth: 1024 
                },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' },
                // CONFIGURACIÓN CRÍTICA: Usar selector CSS para el corte
                pagebreak:    { mode: ['css', 'legacy'], before: '.saltopagina' }
            };

            html2pdf().set(opt).from(element).save()
                .then(() => { prepararVistaParaPDF(false); })
                .catch(err => { console.error(err); prepararVistaParaPDF(false); });
        }

        function exportCombinationToPDF(i) {
            const element = document.getElementById('comb-' + i);
            if (!element) return;

            const btnDiv = element.querySelector('.btn-individual');
            if(btnDiv) btnDiv.style.display = 'none';

            const opt = {
                margin:       0.5,
                filename:     `horario_opcion_${i+1}.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save()
                .then(() => { if(btnDiv) btnDiv.style.display = 'block'; })
                .catch(() => { if(btnDiv) btnDiv.style.display = 'block'; });
        }
        
        <?php if (!empty($data)): ?>
            if (!datosSessionStorage) {
                const dataPHP = <?php echo json_encode($data); ?>;
                mostrarResultadosDelFiltro(dataPHP);
            }
        <?php endif; ?>
    </script>
</body>
</html>